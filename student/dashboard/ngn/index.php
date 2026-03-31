<?php
// Config handles DB + session
include '../../../config.php';

// Hide errors from users in production
$isProduction = !in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']);
if ($isProduction) { error_reporting(0); ini_set('display_errors', 0); }

// Auto-create temporary tables if missing on production DB
mysqli_query($con, "
    CREATE TABLE IF NOT EXISTS `temporary_exam_state` (
        `student_id` int(11) NOT NULL,
        `examTaken` int(11) NOT NULL,
        `question_set` text NOT NULL,
        `current_question` int(11) NOT NULL DEFAULT 0,
        `timer` int(11) NOT NULL DEFAULT 0,
        `updated_at` datetime NOT NULL,
        PRIMARY KEY (`student_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");
mysqli_query($con, "
    CREATE TABLE IF NOT EXISTS `temporary_exam_result` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `examTaken` int(11) NOT NULL,
        `question_uid` varchar(100) NOT NULL,
        `question_type` varchar(50) DEFAULT NULL,
        `question_id` int(11) DEFAULT NULL,
        `user_answer` text,
        `correct_answer` text,
        `isCorrect` tinyint(1) DEFAULT 0,
        `score` float DEFAULT 0,
        `max_points` int(11) DEFAULT 1,
        `earned_points` int(11) DEFAULT 0,
        `rationale` text,
        `topic` varchar(255) DEFAULT NULL,
        `system` varchar(255) DEFAULT NULL,
        `cnc` varchar(255) DEFAULT NULL,
        `dlevel` varchar(100) DEFAULT NULL,
        `time_taken` int(11) DEFAULT 0,
        `totalTime` int(11) DEFAULT 0,
        `question_number` int(11) DEFAULT NULL,
        `timestamp` datetime DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `student_exam` (`student_id`, `examTaken`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
");

// Verify login
if (!isset($_SESSION['user_id'])) {
  header('Location: ' . BASE_URL . 'index.php');
  exit;
}

// Get fullname
$user_id = $_SESSION['user_id'];
$stmt = mysqli_prepare($con, "SELECT fullname, examTaken FROM login WHERE id=? LIMIT 1");
mysqli_stmt_bind_param($stmt, 'i', $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);
$fullname = $user ? $user['fullname'] : 'Student';

// Prevent caching to ensure session variables are always fresh
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

$isNewAttempt = false;

// Check if user is resuming from a saved state via temporary_exam_state
$stateCheck = mysqli_query($con, "SELECT * FROM temporary_exam_state WHERE student_id = '$user_id'");
$savedState = mysqli_fetch_assoc($stateCheck);

$savedTimer = 0;
$startQuestionIndex = 0;
$dbAnswers = [];

if ($savedState) {
  // Resuming an existing session
  $examTaken = intval($savedState['examTaken']);
  $_SESSION['current_ngn_examTaken'] = $examTaken;
  $questionIds = json_decode($savedState['question_set'], true);
  $_SESSION['ngn_exam_set'] = $questionIds;
  $startQuestionIndex = intval($savedState['current_question']);
  $savedTimer = intval($savedState['timer']);
  
  $isNewAttempt = false;

  // Restore previous answers from temporary table
  $ansQ = mysqli_query($con, "SELECT * FROM temporary_exam_result WHERE student_id='$user_id' AND examTaken='$examTaken'");
  while ($arow = mysqli_fetch_assoc($ansQ)) {
    $uid = $arow['question_uid'];
    
    $ansDecoded = json_decode($arow['user_answer'], true);
    if ($ansDecoded === null) $ansDecoded = $arow['user_answer'];
    
    $corrDecoded = json_decode($arow['correct_answer'], true);
    if ($corrDecoded === null) $corrDecoded = $arow['correct_answer'];
    
    $dbAnswers[$uid] = [
        'question_uid' => $uid,
        'question_type' => $arow['question_type'],
        'question_id' => explode('-', $uid)[1] ?? $arow['question_uid'],
        'answer' => $ansDecoded,
        'correct_answer' => $corrDecoded,
        'isCorrect' => intval($arow['isCorrect']),
        'score' => floatval($arow['score']),
        'max_points' => intval($arow['max_points']),
        'earned_points' => intval($arow['earned_points']),
        'rationale' => $arow['rationale'],
        'topic' => $arow['topic'],
        'system' => $arow['system'],
        'cnc' => $arow['cnc'],
        'dlevel' => $arow['dlevel'],
        'time_taken' => intval($arow['time_taken']),
        'totalTime' => intval($arow['totalTime']),
        'examTaken' => intval($arow['examTaken']),
        'question_number' => intval($arow['question_number']),
        'timestamp' => $arow['timestamp']
    ];
  }
} else {
  // Brand new attempt
  $isNewAttempt = true;
  
  // examTaken handling: Increment ONLY ONCE per session to support refresh/persistence
  if (!isset($_SESSION['current_ngn_examTaken'])) {
    $select = mysqli_query($con, "SELECT examTaken FROM `login` WHERE id = '$user_id'");
    $userRow = mysqli_fetch_assoc($select);
    $currentExamTaken = isset($userRow['examTaken']) ? intval($userRow['examTaken']) : 0;

    // Increment for new attempt
    $_SESSION['current_ngn_examTaken'] = $currentExamTaken + 1;
    mysqli_query($con, "UPDATE login SET examTaken = examTaken + 1 WHERE id='$user_id'");

    // Force reset start state for brand new sessions
    $_SESSION['ngn_exam_set'] = null;
  }
  $examTaken = $_SESSION['current_ngn_examTaken'];
  
  // Fetch new questions if none generated yet
  $questionIds = [];
  function table_exists($con, $table) {
    $table = mysqli_real_escape_string($con, $table);
    $res = mysqli_query($con, "SHOW TABLES LIKE '$table'");
    return $res && mysqli_num_rows($res) > 0;
  }

  $questionTypes = [
    [['highlight'], 'highlight', 3],
    [['btq'], 'bowtie', 1],
    [['mmr'], 'mmr', 2],
    [['mpr'], 'mpr', 2],
    [['dragndrop'], 'dragndrop', 2],
    [['dropdown', 'dropdown_questions'], 'dropdown', 2],
    [['sata'], 'sata', 2],
    [['column'], 'column', 2],
    [['traditional'], 'traditional', 2],
  ];

  foreach ($questionTypes as [$candidateTables, $type, $limit]) {
    if (!isset($_SESSION['ngn_exam_set'])) {
      foreach ($candidateTables as $table) {
        if (!table_exists($con, $table)) continue;
        $q = mysqli_query($con, "SELECT id, '$type' AS type FROM `$table` ORDER BY RAND() LIMIT $limit");
        if ($q) {
          while ($r = mysqli_fetch_assoc($q)) $questionIds[] = $r;
        }
        break; // use first existing table for this type
      }
    }
  }

  if (!isset($_SESSION['ngn_exam_set'])) {
    if (count($questionIds) === 0) die("No questions available.");
    shuffle($questionIds);
    $_SESSION['ngn_exam_set'] = $questionIds;
  } else {
    $questionIds = $_SESSION['ngn_exam_set'];
  }
}

$isNewAttemptJs = $isNewAttempt ? 'true' : 'false';
$questionIdsJs = json_encode($questionIds);
$examTakenJs = json_encode($examTaken);
$userIdJs = json_encode($user_id);
$totalQuestionsJs = json_encode(count($questionIds));
$fullnameJs = json_encode($fullname);
$savedTimerJs = json_encode($savedTimer);
$startQuestionIndexJs = json_encode($startQuestionIndex);
$dbAnswersJs = json_encode($dbAnswers);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NCLEX NGN Exam — Studium</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --primary: #0a1628;
      --primary-light: #1e3a5f;
      --accent: #3b82f6;
      --accent-glow: rgba(59, 130, 246, 0.3);
      --success: #10b981;
      --danger: #ef4444;
      --warning: #f59e0b;
      --surface: #ffffff;
      --surface-alt: #f8fafc;
      --text: #0f172a;
      --text-muted: #64748b;
      --border: #e2e8f0;
      --radius: 14px;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    html,
    body {
      height: 100%;
      margin: 0;
      padding: 0;
      overflow: hidden;
      /* Prevent body scroll */
    }

    body {
      font-family: 'Inter', sans-serif;
      background: var(--surface-alt);
      color: var(--text);
      display: flex;
      flex-direction: column;
    }

    /* ===== TOP NAVBAR (Responsive) ===== */
    .navbar {
      background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
      color: white;
      padding: 0 12px;
      height: 56px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
      position: relative;
      z-index: 100;
      flex-shrink: 0;
    }

    @media (max-width: 640px) {
      .nav-brand span {
        display: none;
      }

      .user-badge {
        display: none;
      }

      .nav-divider {
        display: none;
      }

      .question-tag-box {
        transform: scale(0.9);
      }

      .question-type-badge {
        font-size: 8px;
        padding: 2px 8px;
      }

      .timer-box {
        scale: 0.9;
        transform-origin: right;
      }
    }

    .nav-left {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .nav-brand {
      font-size: 18px;
      font-weight: 800;
      letter-spacing: -0.5px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .nav-brand i {
      font-size: 20px;
      color: var(--accent);
    }

    .nav-divider {
      width: 1px;
      height: 28px;
      background: rgba(255, 255, 255, 0.15);
    }

    .question-type-badge {
      background: rgba(59, 130, 246, 0.2);
      border: 1px solid rgba(59, 130, 246, 0.3);
      color: #93c5fd;
      padding: 4px 14px;
      border-radius: 100px;
      font-size: 11px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      transition: all 0.3s ease;
    }

    .nav-center {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .progress-info {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .progress-ring-container {
      position: relative;
      width: 40px;
      height: 40px;
    }

    .progress-ring {
      transform: rotate(-90deg);
    }

    .progress-ring-bg {
      fill: none;
      stroke: rgba(255, 255, 255, 0.1);
      stroke-width: 3;
    }

    .progress-ring-fill {
      fill: none;
      stroke: var(--accent);
      stroke-width: 3;
      stroke-linecap: round;
      transition: stroke-dashoffset 0.6s cubic-bezier(0.4, 0, 0.2, 1);
      filter: drop-shadow(0 0 4px var(--accent-glow));
    }

    .progress-ring-text {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 10px;
      font-weight: 800;
      color: white;
    }

    .progress-label {
      font-size: 13px;
      font-weight: 600;
      color: rgba(255, 255, 255, 0.9);
    }

    .progress-label span {
      color: rgba(255, 255, 255, 0.5);
      font-weight: 400;
    }

    .nav-right {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .user-badge {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.08);
      padding: 6px 14px;
      border-radius: 100px;
      font-size: 12px;
      font-weight: 500;
      border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .user-badge i {
      color: var(--accent);
      font-size: 14px;
    }

    .timer-box {
      display: flex;
      align-items: center;
      gap: 8px;
      background: rgba(255, 255, 255, 0.06);
      padding: 8px 16px;
      border-radius: 10px;
      border: 1px solid rgba(255, 255, 255, 0.08);
    }

    .timer-icon {
      color: var(--warning);
      font-size: 14px;
      animation: pulse-timer 2s infinite;
    }

    @keyframes pulse-timer {

      0%,
      100% {
        opacity: 1;
      }

      50% {
        opacity: 0.5;
      }
    }

    .timer-text {
      font-family: 'Inter', monospace;
      font-size: clamp(14px, 4vw, 16px);
      font-weight: 700;
      letter-spacing: 0.5px;
    }

    @media (max-width: 480px) {
      .timer-box {
        padding: 4px 10px;
      }

      .progress-label {
        display: none;
      }
    }

    /* ===== MAIN CONTENT ===== */
    .main-content {
      flex: 1;
      display: flex;
      overflow: hidden;
    }

    /* ===== TOOLS SIDEBAR (Responsive) ===== */
    .tools-nav {
      width: 72px;
      background: white;
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 16px 0;
      gap: 12px;
      transition: all 0.3s ease;
    }

    @media (max-width: 768px) {
      .tools-nav-wrapper {
        width: 0;
        border: 0;
        background: transparent;
      }

      .tools-nav {
        position: fixed;
        bottom: calc(170px + env(safe-area-inset-bottom));
        right: 12px;
        width: auto;
        background: transparent;
        border: none;
        flex-direction: column-reverse;
        /* Tools stack up */
        pointer-events: none;
        z-index: 1100;
        padding: 0;
        opacity: 0;
        transform: translateY(20px);
        gap: 10px;
        max-height: calc(100vh - 180px);
        overflow-y: auto;
        overscroll-behavior: contain;
      }

      .tools-nav.active {
        pointer-events: auto;
        opacity: 1;
        transform: translateY(0);
      }
    }

    .tool-btn {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: all 0.2s;
      color: var(--text-muted);
      border: 1px solid var(--border);
      background: white;
    }

    @media (max-width: 768px) {
      .tool-btn {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
      }

      .tool-btn span {
        display: none;
      }
    }

    .tool-btn i {
      font-size: 18px;
    }

    .tool-btn span {
      font-size: 8px;
      font-weight: 800;
      text-transform: uppercase;
      margin-top: 4px;
    }

    .question-nav {
      flex: 1;
      width: 72px;
      background: #fcfcfd;
      border-right: 1px solid var(--border);
      display: flex;
      flex-direction: column;
      align-items: center;
      padding: 16px 0;
      gap: 6px;
      overflow-y: auto;
      scrollbar-width: thin;
    }

    .question-nav::-webkit-scrollbar {
      width: 4px;
    }

    .question-nav::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 10px;
    }

    .nav-dot {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 12px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
      border: 2px solid transparent;
      color: var(--text-muted);
      background: var(--surface-alt);
      position: relative;
    }

    .nav-dot:hover {
      background: #e2e8f0;
      transform: scale(1.08);
    }

    .nav-dot.current {
      background: var(--accent);
      color: white;
      border-color: var(--accent);
      box-shadow: 0 4px 12px var(--accent-glow);
      transform: scale(1.1);
    }

    .nav-dot.answered {
      background: #dcfce7;
      color: #166534;
      border-color: #86efac;
    }

    .nav-dot.answered.current {
      background: var(--accent);
      color: white;
      border-color: var(--accent);
    }

    /* ===== IFRAME AREA ===== */
    .iframe-container {
      flex: 1;
      display: flex;
      flex-direction: column;
      background: var(--surface-alt);
    }

    .iframe-wrapper {
      flex: 1;
      padding: clamp(8px, 2vw, 24px);
      display: flex;
      align-items: stretch;
      overflow: hidden;
    }

    iframe {
      width: 100%;
      height: 100%;
      border: none;
      border-radius: var(--radius);
      background: white;
      box-shadow:
        0 1px 3px rgba(0, 0, 0, 0.04),
        0 4px 12px rgba(0, 0, 0, 0.06);
    }

    /* ===== BOTTOM CONTROLS ===== */
    .controls-bar {
      padding: 12px 16px;
      background: white;
      border-top: 1px solid var(--border);
      display: flex;
      align-items: center;
      justify-content: space-between;
      height: 64px;
    }

    @media (max-width: 480px) {
      .btn span {
        display: none;
      }

      /* Hide text on very small screens */
      .btn {
        padding: 10px 16px;
      }
    }

    .controls-left,
    .controls-right {
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .btn {
      padding: 10px 24px;
      border: none;
      border-radius: 10px;
      font-family: 'Inter', sans-serif;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn:disabled {
      opacity: 0.4;
      cursor: not-allowed;
      transform: none !important;
    }

    .btn-secondary {
      background: var(--surface-alt);
      color: var(--text);
      border: 1px solid var(--border);
    }

    .btn-secondary:hover:not(:disabled) {
      background: #e2e8f0;
      transform: translateY(-1px);
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--accent) 0%, #2563eb 100%);
      color: white;
      box-shadow: 0 2px 8px var(--accent-glow);
    }

    .btn-primary:hover:not(:disabled) {
      transform: translateY(-1px);
      box-shadow: 0 4px 16px var(--accent-glow);
    }

    .btn-danger {
      background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
      color: white;
      box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }

    .btn-danger:hover:not(:disabled) {
      transform: translateY(-1px);
      box-shadow: 0 4px 16px rgba(239, 68, 68, 0.3);
    }

    /* MOBILE SQUEEZE FOR CONTROLS */
    @media (max-width: 640px) {
      .controls-bar {
        padding: 8px 12px;
        height: auto;
      }

      .btn {
        padding: 10px 16px;
        font-size: 13px;
        font-weight: 700;
        gap: 4px;
      }

      .btn i {
        font-size: 14px;
      }

      .controls-right {
        display: flex;
        gap: 8px;
      }
    }

    .progress-bar-bottom {
      height: 3px;
      background: var(--border);
      position: relative;
    }

    .progress-bar-fill {
      height: 100%;
      background: linear-gradient(90deg, var(--accent), #8b5cf6);
      border-radius: 0 3px 3px 0;
      transition: width 0.6s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* ===== MODAL ===== */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.5);
      backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 1300;
    }

    .modal-overlay.active {
      display: flex;
      animation: fadeIn 0.2s ease;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    .modal-box {
      background: white;
      border-radius: 20px;
      padding: 32px;
      max-width: 420px;
      width: 90%;
      text-align: center;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
      animation: slideUp 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes slideUp {
      from {
        transform: translateY(20px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .modal-icon {
      width: 64px;
      height: 64px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 16px;
      font-size: 28px;
    }

    .modal-icon.warning {
      background: #fef3c7;
      color: var(--warning);
    }

    .modal-title {
      font-size: 20px;
      font-weight: 800;
      margin-bottom: 8px;
    }

    .modal-text {
      font-size: 14px;
      color: var(--text-muted);
      margin-bottom: 24px;
      line-height: 1.6;
    }

    .modal-text strong {
      color: var(--text);
    }

    .modal-actions {
      display: flex;
      gap: 10px;
      justify-content: center;
    }

    .modal-actions .btn {
      padding: 12px 28px;
      font-size: 14px;
    }

    /* ===== RESPONSIVE ===== */
    @media (max-width: 768px) {
      .question-nav {
        display: none;
      }

      .nav-center {
        display: none;
      }

      .navbar {
        height: 56px;
        padding: 0 16px;
      }

      .iframe-wrapper {
        padding: 8px;
      }

      .controls-bar {
        padding: 10px 16px;
      }
    }
  </style>
</head>

<body>

  <!-- Navbar -->
  <div class="navbar">
    <div class="nav-left">
      <div class="nav-brand">
        <i class="fas fa-graduation-cap"></i>
        <span>NCLEX NGN Exam</span>
      </div>
      <div class="nav-divider"></div>
      <span class="question-type-badge" id="questionTag">—</span>
    </div>

    <div class="nav-center">
      <div class="progress-info">
        <div class="progress-ring-container">
          <svg class="progress-ring" width="40" height="40">
            <circle class="progress-ring-bg" cx="20" cy="20" r="16" />
            <circle class="progress-ring-fill" id="progressRing" cx="20" cy="20" r="16" stroke-dasharray="100.53"
              stroke-dashoffset="100.53" />
          </svg>
          <span class="progress-ring-text" id="progressPercent">0%</span>
        </div>
        <div class="progress-label" id="questionProgress">
          Question <strong>1</strong> <span>of <?php echo count($questionIds); ?></span>
        </div>
      </div>
    </div>

    <div class="nav-right">
      <div class="user-badge">
        <i class="fas fa-user"></i>
        <?php echo htmlspecialchars($fullname); ?>
      </div>
      <div class="timer-box">
        <i class="fas fa-clock timer-icon"></i>
        <span class="timer-text" id="timer">00:00:00</span>
      </div>
    </div>
  </div>

  <!-- Progress bar thin line -->
  <div class="progress-bar-bottom">
    <div class="progress-bar-fill" id="progressBarFill" style="width: 0%"></div>
  </div>

  <!-- Pre-Exam Instructions Modal -->
  <div class="modal-overlay active" id="startModal" style="background: rgba(10, 22, 40, 0.98); z-index: 9999;">
    <div class="modal-box !max-h-[95vh] !overflow-y-auto custom-scrollbar"
      style="width: 100%; max-width: 500px; padding: clamp(24px, 5vw, 40px); text-align: left; border-radius: min(32px, 5vw); box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
      <div
        class="w-12 h-12 md:w-16 md:h-16 bg-amber-100 rounded-xl md:rounded-2xl flex items-center justify-center text-amber-600 text-2xl md:text-3xl mb-6 md:mb-8 shadow-sm">
        <i class="fas fa-shield-halved"></i>
      </div>

      <h2 class="text-2xl md:text-3xl font-black text-slate-900 tracking-tight leading-tight mb-4">Security Protocol
        <br>& Guidelines
      </h2>

      <div class="modal-text" style="text-align:left;">
        <!-- Alert Box -->
        <div
          class="p-4 md:p-5 bg-red-50 border border-red-100 rounded-xl md:rounded-2xl mb-6 md:mb-8 flex items-start gap-3 md:gap-4 shadow-sm">
          <div
            class="w-6 h-6 md:w-8 md:h-8 shrink-0 bg-red-500 text-white rounded-full flex items-center justify-center text-[10px] md:text-sm shadow-lg shadow-red-200">
            <i class="fas fa-exclamation"></i>
          </div>
          <div class="text-[12px] md:text-[13px] text-red-900 leading-relaxed pt-0.5">
            <strong>ZERO TOLERANCE:</strong> Leaving this tab or clicking outside of this window (Taskbar, dual
            monitors, or other apps) will result in <span class="font-bold underline">Instant Termination</span>.
          </div>
        </div>

        <div class="space-y-3 md:space-y-4">
          <label
            class="group flex items-center p-4 md:p-5 bg-slate-50 border border-slate-100 rounded-xl md:rounded-2xl cursor-pointer transition-all hover:bg-slate-100 hover:border-slate-200">
            <input type="checkbox" id="agreeRules"
              class="w-5 h-5 md:w-6 md:h-6 shrink-0 accent-blue-600 mr-4 cursor-pointer">
            <span class="text-[13px] md:text-[14px] font-bold text-slate-700 leading-tight">I agree to the NCLEX
              Security Guidelines.</span>
          </label>

          <label
            class="group flex items-center p-4 md:p-5 bg-red-50/30 border border-red-100 rounded-xl md:rounded-2xl cursor-pointer transition-all hover:bg-red-50/50 hover:border-red-200">
            <input type="checkbox" id="agreeFocus"
              class="w-5 h-5 md:w-6 md:h-6 shrink-0 accent-red-600 mr-4 cursor-pointer">
            <span class="text-[13px] md:text-[14px] font-bold text-red-800 leading-tight">I understand that clicking
              outside this window will immediately fail my exam.</span>
          </label>
        </div>
      </div>

      <div class="mt-8 md:mt-10">
        <button
          class="btn btn-primary w-full py-4 md:py-5 rounded-xl md:rounded-2xl text-[16px] md:text-lg font-black tracking-wide flex items-center justify-center gap-3 disabled:opacity-30 disabled:grayscale transition-all"
          id="startExamBtn" disabled>
          <i class="fas fa-play-circle text-xl md:text-2xl"></i> Start Examination
        </button>
        <p class="text-center text-[9px] md:text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-4">Safe
          Exam Browser Mode — Active</p>
      </div>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <!-- Tools Sidebar -->
    <div class="tools-nav-wrapper h-full flex flex-col border-r border-slate-200 bg-white">
      <div class="tools-nav" id="toolsNav">
        <div class="tool-btn" id="calcBtn" title="Calculator">
          <i class="fas fa-calculator"></i>
          <span>Calc</span>
        </div>
        <div class="tool-btn" id="noteBtn" title="My Notes">
          <i class="fas fa-sticky-note"></i>
          <span>Notes</span>
        </div>
        <div class="tool-btn" id="fullBtn" title="Toggle Fullscreen">
          <i class="fas fa-expand"></i>
          <span>Size</span>
        </div>
        <div class="tool-btn" id="feedBtn" title="Feedback">
          <i class="fas fa-comment-dots"></i>
          <span>Feed</span>
        </div>
        <div class="tool-btn" id="continueBtn" title="Continue Later" style="color:#ef4444;">
          <i class="fas fa-pause-circle"></i>
          <span>Pause</span>
        </div>
      </div>
    </div>

    <!-- Mobile Tools Toggle Button -->
    <div class="md:hidden fixed bottom-[92px] right-3 z-[1200] pointer-events-auto">
      <button
        class="w-12 h-12 bg-blue-600 text-white rounded-full shadow-2xl flex items-center justify-center text-lg transition-transform active:scale-90 border-2 border-white/20"
        onclick="document.getElementById('toolsNav').classList.toggle('active'); this.classList.toggle('rotate-45');">
        <i class="fas fa-plus"></i>
      </button>
    </div>

    <!-- Question Frame Container -->
    <div class="iframe-container flex-1 flex flex-col bg-slate-100 overflow-hidden">
      <div class="iframe-wrapper flex-1 p-2 md:p-6 flex flex-col overflow-hidden">
        <iframe id="questionFrame" class="w-full h-full border-0 md:rounded-2xl bg-white shadow-xl" src=""></iframe>
      </div>

      <!-- Bottom Navigation Bar -->
      <div
        class="controls-bar w-full h-16 px-4 md:px-8 bg-white border-t border-slate-200 flex items-center justify-between shrink-0">
        <div class="controls-left">
          <button class="btn btn-secondary flex items-center gap-2" id="prevBtn" disabled>
            <i class="fas fa-chevron-left"></i> <span>Previous</span>
          </button>
        </div>
        <div class="controls-right flex items-center gap-3">

          <button class="btn btn-primary flex items-center gap-2" id="nextBtn" disabled>
            <span>Next</span> <i class="fas fa-chevron-right"></i>
          </button>
        </div>
      </div>
    </div>
  </div>



  <!-- Calculator Modal -->
  <div class="modal-overlay" id="calcModal" style="background:transparent; pointer-events: none; z-index: 1300;">
    <div class="modal-box"
      style="pointer-events: auto; width: 280px; padding: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border: 1px solid var(--border);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <span
          style="font-weight:800; font-size:12px; text-transform:uppercase; color:var(--text-muted);">Calculator</span>
        <i class="fas fa-times" style="cursor:pointer;"
          onclick="document.getElementById('calcModal').style.display='none'"></i>
      </div>
      <input type="text" id="calcDisplay" readonly
        style="width:100%; border:1px solid var(--border); padding:10px; text-align:right; font-family:monospace; font-size:20px; border-radius:8px; margin-bottom:10px; background:#f8fafc;">
      <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:8px;">
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('C')">C</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('/')">/</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('*')">*</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('-')">-</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('7')">7</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('8')">8</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('9')">9</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('+')">+</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('4')">4</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('5')">5</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('6')">6</button>
        <button class="btn btn-primary" style="padding:10px; grid-row: span 2;" onclick="calcInput('=')">=</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('1')">1</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('2')">2</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('3')">3</button>
        <button class="btn btn-secondary" style="padding:10px; grid-column: span 2;" onclick="calcInput('0')">0</button>
        <button class="btn btn-secondary" style="padding:10px" onclick="calcInput('.')">.</button>
      </div>
    </div>
  </div>

  <!-- Notes Modal -->
  <div class="modal-overlay" id="noteModal" style="background:transparent; pointer-events: none; z-index: 1300;">
    <div class="modal-box" style="pointer-events: auto; width: 350px; padding: 20px; border: 1px solid var(--border);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <span style="font-weight:800; font-size:12px; text-transform:uppercase; color:var(--text-muted);">Quick
          Note</span>
        <i class="fas fa-times" style="cursor:pointer;"
          onclick="document.getElementById('noteModal').style.display='none'"></i>
      </div>
      <textarea id="examNote"
        style="width:100%; height:200px; border:1px solid var(--border); border-radius:10px; padding:12px; font-size:14px; resize:none;"
        placeholder="Type your clinical notes here..."></textarea>
      <div style="margin-top:12px; font-size:10px; color:var(--text-muted); text-align:left;">Notes are for reference
        only and won't be saved on refresh.</div>
    </div>
  </div>

  <!-- Feedback Modal -->
  <div class="modal-overlay" id="feedModal" style="background:transparent; pointer-events: none; z-index: 1300;">
    <div class="modal-box" style="pointer-events: auto; width: 320px; padding: 24px; border: 1px solid var(--border);">
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:16px;">
        <span style="font-weight:800; font-size:12px; text-transform:uppercase; color:var(--text-muted);">Flash
          Feedback</span>
        <i class="fas fa-times" style="cursor:pointer;"
          onclick="document.getElementById('feedModal').style.display='none'"></i>
      </div>
      <div style="display:flex; flex-direction:column; gap:8px;">
        <button class="btn btn-secondary" style="justify-content:flex-start; font-size:12px;"
          onclick="logFeedback('Technical Issue')"><i class="fas fa-bug"></i> Bug/Technical Issue</button>
        <button class="btn btn-secondary" style="justify-content:flex-start; font-size:12px;"
          onclick="logFeedback('Calculation Help')"><i class="fas fa-calculator"></i> Calculation Help</button>
        <button class="btn btn-secondary" style="justify-content:flex-start; font-size:12px;"
          onclick="logFeedback('Vague Question')"><i class="fas fa-question"></i> Vague Question</button>
      </div>
    </div>
  </div>

  <script>
    function calcInput(val) {
      const d = document.getElementById('calcDisplay');
      if (val === 'C') d.value = '';
      else if (val === '=') { try { d.value = eval(d.value); } catch (e) { d.value = 'Total Error'; } }
      else d.value += val;
    }
    function logFeedback(type) {
      Swal.fire({ icon: 'success', title: 'Success', text: 'Your feedback on "' + type + '" has been logged.' });
      document.getElementById('feedModal').style.display = 'none';
    }

    // Security: Disable Right Click
    document.addEventListener('contextmenu', e => {
      e.preventDefault();
      Swal.fire({ icon: 'warning', title: 'Security', text: 'Right-click is disabled during the examination.', timer: 2000, showConfirmButton: false });
    });

    let isExiting = false;

    // Security: Detect Tab Switching & Window Focus Loss
    document.addEventListener('visibilitychange', () => {
      if (document.hidden && !isExiting) {
        window.location.href = 'security_violation.php?reason=tab_switch';
      }
    });

    window.addEventListener('blur', () => {
      // Add a tiny buffer to allow clinical interactions with the question iframes
      setTimeout(() => {
        if (isExiting || (typeof startModal !== 'undefined' && startModal.classList.contains('active'))) return;

        // If focus shifted to our own question frame, it's safe
        if (document.activeElement && document.activeElement.tagName === 'IFRAME') return;

        // Otherwise, they clicked out to another app or screen
        window.location.href = 'security_violation.php?reason=window_focus_lost';
      }, 200);
    });

    // Security: Disable Keyboard Inspection
    document.onkeydown = function (e) {
      if (e.keyCode == 123) return false; // F12
      if (e.ctrlKey && e.shiftKey && e.keyCode == 'I'.charCodeAt(0)) return false; // Ctrl+Shift+I
      if (e.ctrlKey && e.shiftKey && e.keyCode == 'C'.charCodeAt(0)) return false; // Ctrl+Shift+C
      if (e.ctrlKey && e.shiftKey && e.keyCode == 'J'.charCodeAt(0)) return false; // Ctrl+Shift+J
      if (e.ctrlKey && e.keyCode == 'U'.charCodeAt(0)) return false; // Ctrl+U
    };

    // Security: Confirm Termination on Back Button
    (function () {
      window.history.pushState(null, "", window.location.href);
      window.onpopstate = function () {
        // Immediately push back to prevent immediate exit while modal is open
        window.history.pushState(null, "", window.location.href);

        Swal.fire({
          title: 'Confirm Exam Termination?',
          text: 'Using the browser back button is a violation. If you continue, your ongoing progress will be PERMANENTLY DELETED and your exam will be cancelled. To save your progress instead, use the "Continue Later" button.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#ef4444',
          cancelButtonColor: '#3b82f6',
          confirmButtonText: 'Yes, Terminate & Drop Data',
          cancelButtonText: 'Return to Exam'
        }).then(async (result) => {
          if (result.isConfirmed) {
            isExiting = true;
            
            // Show a loading message while we drop tables
            Swal.fire({
              title: 'Terminating...',
              text: 'Clearing temporary session records...',
              allowOutsideClick: false,
              didOpen: () => { Swal.showLoading(); }
            });

            try {
              // Drop tables immediately
              await fetch('cancel_exam.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ examTaken: examTaken })
              });
              
              localStorage.removeItem(`ngn_ans_${authUserId}_${examTaken}`);
              localStorage.removeItem(`ngn_exam_${authUserId}_${examTaken}`);
              
              window.location.href = '../index.php'; // Back to student dash
            } catch (err) {
              window.location.href = '../index.php';
            }
          }
        });
      };
    })();
  </script>
  <script>
    const questionIds = <?php echo $questionIdsJs; ?>;
    const examTaken = <?php echo $examTakenJs; ?>;
    const authUserId = <?php echo $userIdJs; ?>;
    const totalQuestions = <?php echo $totalQuestionsJs; ?>;
    const isNewAttempt = <?php echo $isNewAttemptJs; ?>;

    // If this is a fresh start of a sequence, clear the cache for this specific attempt
    if (isNewAttempt) {
      localStorage.removeItem(`ngn_ans_${authUserId}_${examTaken}`);
    }

    // Tool Sidebars Toggles
    document.getElementById('calcBtn').addEventListener('click', () => toggleTool('calcModal'));
    document.getElementById('noteBtn').addEventListener('click', () => toggleTool('noteModal'));
    document.getElementById('feedBtn').addEventListener('click', () => toggleTool('feedModal'));
    document.getElementById('continueBtn').addEventListener('click', () => {
      Swal.fire({
        title: 'Continue Later?',
        text: "Your exam state, including your timer and current question, will be paused securely. You can resume from the dashboard later.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#64748b',
        confirmButtonText: 'Yes, Pause Exam',
        cancelButtonText: 'Keep Testing'
      }).then(async (result) => {
        if (result.isConfirmed) {
          isExiting = true;
          
          try {
            const response = await fetch('state_manager.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify({
                examTaken: examTaken,
                question_set: questionIds,
                current_question: currentQuestion,
                timer: totalSeconds
              })
            });
            
            const reqResult = await response.json();
            if(!reqResult.ok) throw new Error('State save failed');
            
            // clear local storage since state is effectively transferred server side
            localStorage.removeItem(`ngn_ans_${authUserId}_${examTaken}`);
            localStorage.removeItem(examSessionId);
            
            window.location.href = '../index.php'; // redirect to dashboard
          } catch(err) {
             Swal.fire('Error', 'Failed to pause exam state. Please try again.', 'error');
             isExiting = false;
          }
        }
      });
    });
    document.getElementById('fullBtn').addEventListener('click', toggleFullScreen);

    function toggleTool(id) {
      const el = document.getElementById(id);
      el.style.display = (el.style.display === 'flex') ? 'none' : 'flex';
    }

    function toggleFullScreen() {
      if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen();
        document.getElementById('fullBtn').innerHTML = '<i class="fas fa-compress"></i><span>Size</span>';
      } else {
        if (document.exitFullscreen) {
          document.exitFullscreen();
          document.getElementById('fullBtn').innerHTML = '<i class="fas fa-expand"></i><span>Size</span>';
        }
      }
    }

    // ===== RECOVER STATE =====
    const answerCacheKey = `ngn_ans_${authUserId}_${examTaken}`;
    const dbAnswers = <?php echo $dbAnswersJs; ?>;
    let localAnswers = JSON.parse(localStorage.getItem(answerCacheKey)) || {};
    let userAnswers = Object.assign({}, dbAnswers, localAnswers);

    function saveAnswersToCache() {
      localStorage.setItem(answerCacheKey, JSON.stringify(userAnswers));
    }

    const savedTimer = <?php echo $savedTimerJs; ?>;
    let totalSeconds = savedTimer;
    let currentQuestion = <?php echo $startQuestionIndexJs; ?>;

    async function saveStateToServer() {
      if (isExiting) return;
      try {
        await fetch('state_manager.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            examTaken: examTaken,
            question_set: questionIds,
            current_question: currentQuestion,
            timer: totalSeconds
          })
        });
      } catch (err) {
        console.warn('Background state save failed:', err);
      }
    }



    // Update header terminology
    function updateTypeBadge(type) {
      const badge = document.getElementById('questionTag');
      if (!badge) return;
      const t = type.toLowerCase();
      if (t === 'mmr') badge.innerText = 'MMR';
      else if (t === 'btq' || t === 'bowtie') badge.innerText = 'Bow-Tie';
      else if (t === 'sata') badge.innerText = 'SATA';
      else badge.innerText = type.toUpperCase();
    }
    let questionStartTime = Date.now();

    // ===== TIMER =====
    const examSessionId = `ngn_exam_${authUserId}_${examTaken}`;
    let startTime = localStorage.getItem(examSessionId);
    
    // If resuming via state_manager variables and it's NOT a brand new attempt
    if (!isNewAttempt && savedTimer > 0) {
      startTime = Date.now() - (savedTimer * 1000);
      localStorage.setItem(examSessionId, startTime);
    } else if (!startTime) {
      startTime = Date.now();
      localStorage.setItem(examSessionId, startTime);
    }

    function updateTimer() {
      const now = Date.now();
      totalSeconds = Math.floor((now - startTime) / 1000);
      const h = String(Math.floor(totalSeconds / 3600)).padStart(2, '0');
      const m = String(Math.floor((totalSeconds % 3600) / 60)).padStart(2, '0');
      const s = String(totalSeconds % 60).padStart(2, '0');
      document.getElementById('timer').textContent = `${h}:${m}:${s}`;
    }
    setInterval(updateTimer, 1000);
    updateTimer();

    // ===== PROGRESS RING =====
    function updateProgress() {
      const answered = Object.keys(userAnswers).length;
      const percent = Math.round((answered / totalQuestions) * 100);
      const ring = document.getElementById('progressRing');
      const circumference = 2 * Math.PI * 16; // r=16
      const offset = circumference - (percent / 100) * circumference;
      ring.style.strokeDashoffset = offset;
      document.getElementById('progressPercent').textContent = percent + '%';
      document.getElementById('progressBarFill').style.width = percent + '%';

      // Update next button validation
      const q = questionIds[currentQuestion];
      const uid = `${q.type}-${q.id}`;
      document.getElementById('nextBtn').disabled = !(userAnswers[uid] !== undefined);
    }

    // ===== QUESTION NAVIGATOR =====
    document.querySelectorAll('.nav-dot').forEach(dot => {
      dot.addEventListener('click', () => {
        const idx = parseInt(dot.dataset.index);
        const uid = `${questionIds[idx].type}-${questionIds[idx].id}`;
        // Allow navigation to answered questions or adjacent questions
        if (userAnswers[uid] !== undefined || idx <= currentQuestion + 1) {
          currentQuestion = idx;
          loadQuestion(currentQuestion);
        }
      });
    });

    function updateNavDots() {
      document.querySelectorAll('.nav-dot').forEach(dot => {
        const idx = parseInt(dot.dataset.index);
        const q = questionIds[idx];
        const uid = `${q.type}-${q.id}`;
        dot.classList.remove('current', 'answered');
        if (idx === currentQuestion) dot.classList.add('current');
        if (userAnswers[uid] !== undefined) dot.classList.add('answered');
      });
    }

    // ===== QUESTION TYPE LABELS =====
    const typeLabels = {
      'highlight': { label: 'Highlight', icon: 'fa-highlighter', color: '#f59e0b' },
      'mmr': { label: 'MMR', icon: 'fa-table-cells', color: '#06b6d4' },
      'mpr': { label: 'Multiple Response', icon: 'fa-list-check', color: '#10b981' },
      'btq': { label: 'Bow-Tie', icon: 'fa-diagram-project', color: '#8b5cf6' },
      'bowtie': { label: 'Bow-Tie', icon: 'fa-diagram-project', color: '#8b5cf6' },
      'sata': { label: 'SATA', icon: 'fa-check-double', color: '#14b8a6' },
      'dragndrop': { label: 'Drag & Drop', icon: 'fa-hand', color: '#ec4899' },
      'dropdown': { label: 'Drop-Down', icon: 'fa-caret-down', color: '#6366f1' },
      'column': { label: 'Column Match', icon: 'fa-columns', color: '#f97316' },
      'traditional': { label: 'Multiple Choice', icon: 'fa-circle-dot', color: '#64748b' },
    };

    // ===== LOAD QUESTION =====
    function loadQuestion(index) {
      const q = questionIds[index];
      const iframe = document.getElementById('questionFrame');

      // Update type badge
      const tl = typeLabels[q.type] || { label: q.type, icon: 'fa-question', color: '#64748b' };
      document.getElementById('questionTag').innerHTML =
        `<i class="fas ${tl.icon}" style="margin-right:4px;"></i> ${tl.label}`;

      // Update progress text
      document.getElementById('questionProgress').innerHTML =
        `Question <strong>${index + 1}</strong> <span>of ${totalQuestions}</span>`;

      // Load iframe without adding to the browser history stack
      const targetUrl = `${q.type}/index.php?id=${q.id}&t=${Date.now()}`;
      if (iframe.contentWindow) {
        iframe.contentWindow.location.replace(targetUrl);
      } else {
        iframe.src = targetUrl;
      }

      // Update buttons
      document.getElementById('prevBtn').disabled = index === 0;

      const uid = `${q.type}-${q.id}`;
      document.getElementById('nextBtn').disabled = !(userAnswers[uid] !== undefined);

      // Update next button text on last question
      if (index === questionIds.length - 1) {
        document.getElementById('nextBtn').innerHTML = '<i class="fas fa-flag-checkered"></i> Finish';
      } else {
        document.getElementById('nextBtn').innerHTML = 'Next <i class="fas fa-chevron-right"></i>';
      }

      questionStartTime = Date.now();
      updateNavDots();
      updateProgress();
    }

    // ===== IFRAME LOAD - PREFILL & SECURITY =====
    document.getElementById('questionFrame').addEventListener('load', () => {
      const iframeWindow = document.getElementById('questionFrame').contentWindow;
      const innerDoc = document.getElementById('questionFrame').contentDocument || iframeWindow.document;

      // Security: Global Right Click inside Iframe
      innerDoc.addEventListener('contextmenu', e => {
        e.preventDefault();
        Swal.fire({ icon: 'warning', title: 'Security', text: 'Right-click is disabled throughout the examination.', timer: 2000, showConfirmButton: false });
      });

      // Security: Disable Iframe Keydowns
      innerDoc.onkeydown = document.onkeydown;

      const q = questionIds[currentQuestion];
      const uid = `${q.type}-${q.id}`;
      const prevResult = userAnswers[uid] || null;

      if (prevResult) {
        iframeWindow.postMessage({
          type: 'prefill',
          answer: prevResult.answer ?? [],
          correct_answer: prevResult.correct_answer ?? [],
          initial_answer: prevResult.initial_answer ?? null,
          changes: prevResult.changes ?? null,
          isCorrect: prevResult.isCorrect === 1,
          score: prevResult.score ?? 0,
          rationale: prevResult.rationale ?? '',
          topic: prevResult.topic ?? '',
          system: prevResult.system ?? '',
          cnc: prevResult.cnc ?? '',
          dlevel: prevResult.dlevel ?? '',
          question_id: prevResult.question_id ?? q.id,
          showRationale: true,
          isReview: true  // Flag for review/read-only mode
        }, '*');
        document.getElementById('nextBtn').disabled = false;
      } else {
        iframeWindow.postMessage({ type: 'prefill', answer: [], showRationale: false, isReview: false }, '*');
        document.getElementById('nextBtn').disabled = true;
      }
    });

    // ===== LISTEN FOR ANSWERED =====
    window.addEventListener('message', async (event) => {
      if (!event.data || typeof event.data !== 'object') return;
      if (event.data.type !== 'answered') return;

      const q = questionIds[currentQuestion];
      const uid = `${q.type}-${q.id}`;
      const timeSpent = Math.max(0, Math.round((Date.now() - questionStartTime) / 1000));
      const totalTime = totalSeconds;

      const answer = event.data.answer ?? event.data.highlighted ?? event.data.user_answer ?? [];
      const correctAnswer = event.data.correctAnswer ?? event.data.correct ?? [];
      const isCorrect = !!event.data.correct;
      const initialAnswer = event.data.initial_answer ?? null;  // Capture omitted answer
      const changes = event.data.changes ?? null;               // Capture changes data

      // Normalize score to 0.00–1.00
      let score = 0;
      if (typeof event.data.score !== 'undefined') {
        score = parseFloat(event.data.score);
        // If score > 1, it's likely a percentage (0-100), normalize it
        if (score > 1) score = score / 100;
      } else {
        score = isCorrect ? 1.00 : 0.00;
      }
      score = Math.max(0, Math.min(1.00, score));

      const maxPoints = event.data.max_points ?? 1;
      const earnedPoints = event.data.earned_points ?? (isCorrect ? 1 : 0);
      const topic = event.data.topic ?? null;
      const system = event.data.system ?? null;
      const cnc = event.data.cnc ?? null;
      const dlevel = event.data.dlevel ?? null;

      userAnswers[uid] = {
        question_uid: uid,
        question_type: q.type,
        question_id: q.id,
        answer: answer,
        correct_answer: correctAnswer,
        initial_answer: initialAnswer,     // Add to storage
        changes: changes,                   // Add to storage
        isCorrect: isCorrect ? 1 : 0,
        score: score,
        max_points: maxPoints,
        earned_points: earnedPoints,
        rationale: event.data.rationale ?? null,
        topic: topic,
        system: system,
        cnc: cnc,
        dlevel: dlevel,
        time_taken: timeSpent,
        totalTime: totalTime,
        examTaken: examTaken,
        question_number: currentQuestion + 1,
        timestamp: new Date().toISOString()
      };

      saveAnswersToCache(); // Persist to local storage

      document.getElementById('nextBtn').disabled = false;
      updateNavDots();
      updateProgress();

      // Save to server
      try {
        const response = await fetch('save_history.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(userAnswers[uid])
        });
        const result = await response.json();
        if (!result.ok) throw new Error(result.error || 'Server Error');
      } catch (err) {
        console.warn('Failed to save history:', err);
        Swal.fire({
          icon: 'error', title: 'Sync Error',
          text: 'Recorded locally but failed to save to server.',
          toast: true, position: 'top-end', timer: 3000, showConfirmButton: false
        });
      }
    });

    // ===== NAVIGATION =====
    document.getElementById('nextBtn').addEventListener('click', async () => {
      if (currentQuestion < questionIds.length - 1) {
        currentQuestion++;
        loadQuestion(currentQuestion);
      } else {
        // Submit the exam!
        isExiting = true;
        
        try {
          const response = await fetch('submit_exam.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ examTaken: examTaken })
          });
          
          await response.json();
          
          localStorage.removeItem(`ngn_ans_${authUserId}_${examTaken}`);
          localStorage.removeItem(examSessionId);
          window.location.href = 'result.php?examTaken=' + examTaken + '&finish=1';
        } catch (err) {
          Swal.fire('Error', 'Failed to submit exam data to server.', 'error');
          isExiting = false;
        }
      }
    });

    document.getElementById('prevBtn').addEventListener('click', () => {
      if (currentQuestion > 0) {
        currentQuestion--;
        loadQuestion(currentQuestion);
      }
    });



    // ===== START MODAL LOGIC =====
    const agreeRules = document.getElementById('agreeRules');
    const agreeFocus = document.getElementById('agreeFocus');
    const startExamBtn = document.getElementById('startExamBtn');
    const startModal = document.getElementById('startModal');

    function validateStart() {
      startExamBtn.disabled = !(agreeRules.checked && agreeFocus.checked);
    }

    agreeRules.addEventListener('change', validateStart);
    agreeFocus.addEventListener('change', validateStart);

    startExamBtn.addEventListener('click', () => {
      // Attempt fullscreen for better security
      toggleFullScreen();

      startModal.classList.remove('active');
      // Start timing ONLY when they click start
      if (!isNewAttempt && savedTimer > 0) {
        startTime = Date.now() - (savedTimer * 1000);
      } else {
        startTime = Date.now();
      }
      localStorage.setItem(examSessionId, startTime);
      loadQuestion(currentQuestion);
    });

    // Avoid loading first question until start is clicked
    // loadQuestion(currentQuestion); 

    // ===== PREVENT ACCIDENTAL RELOAD =====
    window.addEventListener('beforeunload', function (e) {
      if (!isExiting && Object.keys(userAnswers).length > 0) {
        e.preventDefault();
        e.returnValue = '';
        return '';
      }
    });
  </script>
</body>

</html>