<?php
include '../../../config.php';

$isCorrect      = $_GET['isCorrect']      ?? null;
$questionId     = $_GET['questionId']     ?? null;
$topics1        = $_GET['topics1']        ?? '';
$system         = $_GET['system']         ?? '';
$cnc            = $_GET['cnc']            ?? '';
$timeTaken      = $_GET['timeTaken']      ?? 0;
$correctAns     = $_GET['correctAns']     ?? '';
$questionNumber = $_GET['questionNumber'] ?? '';
$ans            = $_GET['ans']            ?? '';

$rationale    = '';
$question     = '';
$choices      = [];
$narcan       = '';
$dlevel       = '';
$questionType = '';
$options      = [];
$row          = [];

if (!empty($questionId)) {
    $stmt = $con->prepare("SELECT * FROM review WHERE questionId = ?");
    $stmt->bind_param("i", $questionId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $stmt2 = $con->prepare("SELECT question, rationale, options, choiceA, choiceB, choiceC, choiceD, narcan, dlevel, type FROM question WHERE id = ?");
        $stmt2->bind_param("i", $questionId);
        $stmt2->execute();
        $result2 = $stmt2->get_result();
        if ($row = $result2->fetch_assoc()) {
            $question     = $row['question']  ?? '';
            $rationale    = $row['rationale'] ?? '';
            $options      = !empty($row['options']) ? (json_decode($row['options'], true) ?? []) : [];
            $choices      = [
                $row['choiceA'] ?? '',
                $row['choiceB'] ?? '',
                $row['choiceC'] ?? '',
                $row['choiceD'] ?? '',
            ];
            $narcan       = $row['narcan'] ?? '';
            $dlevel       = $row['dlevel'] ?? '';
            $questionType = $row['type']   ?? '';
        }
    }
}

$correctAnswer  = !empty($correctAns) ? (json_decode($correctAns, true) ?? []) : [];
$selectedAnswer = !empty($ans)        ? (json_decode($ans, true)        ?? []) : [];
if (!is_array($correctAnswer))  $correctAnswer  = [];
if (!is_array($selectedAnswer)) $selectedAnswer = [];
?>
<!DOCTYPE html>
<html lang="en" oncontextmenu="return false" onselectstart="return false" ondragstart="return false">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Question <?= htmlspecialchars($questionNumber) ?> — Studium</title>
  <link rel="shortcut icon" href="../../../img/logo1.svg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; }
    body {
      font-family: 'Inter', sans-serif;
      background: #f1f5f9;
      color: #0f172a;
      min-height: 100vh;
      margin: 0;
    }

    /* Top bar */
    .qp-topbar {
      background: linear-gradient(135deg, #1B4965 0%, #0d9488 100%);
      color: #fff;
      padding: 14px 28px;
      display: flex;
      align-items: center;
      gap: 16px;
      box-shadow: 0 2px 12px rgba(0,0,0,.18);
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .qp-topbar .qp-label {
      font-size: 1rem;
      font-weight: 700;
      letter-spacing: -0.01em;
    }
    .qp-topbar .qp-sublabel {
      font-size: 0.75rem;
      opacity: 0.75;
      margin-top: 1px;
    }
    .qp-back-btn {
      background: rgba(255,255,255,0.15);
      border: 1.5px solid rgba(255,255,255,0.25);
      color: #fff;
      border-radius: 10px;
      padding: 6px 16px;
      font-size: 0.82rem;
      font-weight: 600;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 6px;
      transition: background 0.2s;
      cursor: pointer;
    }
    .qp-back-btn:hover { background: rgba(255,255,255,0.25); color: #fff; }

    /* Result badge in topbar */
    .qp-result-badge {
      margin-left: auto;
      padding: 5px 16px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .qp-result-badge.correct { background: rgba(16,185,129,.2); color: #6ee7b7; }
    .qp-result-badge.wrong   { background: rgba(239,68,68,.2);  color: #fca5a5; }

    /* Layout */
    .qp-body { padding: 28px 24px; max-width: 1400px; margin: 0 auto; }

    /* Question card */
    .qp-card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 2px 12px rgba(0,0,0,.06);
      padding: 28px 28px;
      height: 100%;
    }

    .qp-q-number {
      font-size: 0.72rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #0d9488;
      margin-bottom: 8px;
    }
    .qp-q-text {
      font-size: 1rem;
      line-height: 1.7;
      color: #0f172a;
      margin-bottom: 24px;
    }

    /* Choice rows */
    .qp-choice {
      border-radius: 10px;
      padding: 12px 16px;
      margin-bottom: 8px;
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 0.88rem;
      font-weight: 500;
      border: 2px solid transparent;
      transition: all 0.15s;
    }
    .qp-choice.neutral  { background: #f8fafc; border-color: #e2e8f0; color: #475569; }
    .qp-choice.correct  { background: #d1fae5; border-color: #10b981; color: #065f46; }
    .qp-choice.selected-wrong { background: #fee2e2; border-color: #ef4444; color: #991b1b; }
    .qp-choice-icon { width: 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.82rem; font-weight: 700; }
    .qp-choice.correct .qp-choice-icon  { background: #10b981; color: #fff; }
    .qp-choice.selected-wrong .qp-choice-icon { background: #ef4444; color: #fff; }
    .qp-choice.neutral .qp-choice-icon  { background: #e2e8f0; color: #64748b; }

    /* Tags */
    .qp-tags {
      border-top: 1.5px solid #f1f5f9;
      margin-top: 22px;
      padding-top: 18px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 8px 20px;
    }
    .qp-tag-item { font-size: 0.78rem; color: #64748b; }
    .qp-tag-item strong { color: #0f172a; }

    /* Rationale card */
    .qp-rationale-text {
      font-size: 0.88rem;
      line-height: 1.75;
      color: #334155;
      margin-bottom: 20px;
    }
    .qp-narc-box {
      background: linear-gradient(135deg, #eff6ff, #f0fdf4);
      border: 1.5px solid #bfdbfe;
      border-radius: 12px;
      padding: 18px 20px;
    }
    .qp-narc-title {
      font-size: 0.82rem;
      font-weight: 700;
      color: #1B4965;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 7px;
    }
    .qp-narc-body { font-size: 0.82rem; line-height: 1.75; color: #334155; }
  </style>
</head>
<body>

<!-- Top bar -->
<div class="qp-topbar">
  <button class="qp-back-btn" onclick="window.close(); history.back();">
    <i class="bi bi-arrow-left"></i> Back
  </button>
  <div>
    <div class="qp-label">Question <?= htmlspecialchars($questionNumber) ?></div>
    <div class="qp-sublabel"><?= htmlspecialchars($topics1) ?> &mdash; <?= htmlspecialchars($system) ?></div>
  </div>
  <?php if ($isCorrect !== null): ?>
  <div class="qp-result-badge <?= $isCorrect ? 'correct' : 'wrong' ?>">
    <?php if ($isCorrect): ?>
      <i class="bi bi-check-circle-fill"></i> Correct
    <?php else: ?>
      <i class="bi bi-x-circle-fill"></i> Incorrect
    <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<!-- Body -->
<div class="qp-body">
  <div class="row g-4">

    <!-- Left: Question + Choices -->
    <div class="col-lg-6">
      <div class="qp-card">
        <div class="qp-q-number"><i class="bi bi-patch-question-fill me-1"></i> Question <?= htmlspecialchars($questionNumber) ?></div>
        <div class="qp-q-text"><?= nl2br(htmlspecialchars($question)) ?></div>

        <?php if (strtoupper($questionType) === 'SATA'): ?>
          <?php foreach ($options as $index => $option):
            $isCorrectOpt  = in_array($index, $correctAnswer);
            $isSelectedOpt = in_array($index, $selectedAnswer);
            $cls = $isCorrectOpt ? 'correct' : ($isSelectedOpt && !$isCorrectOpt ? 'selected-wrong' : 'neutral');
            $icon = $isCorrectOpt ? '<i class="bi bi-check-lg"></i>' : ($isSelectedOpt ? '<i class="bi bi-x-lg"></i>' : '');
            $label = chr(65 + $index);
          ?>
          <div class="qp-choice <?= $cls ?>">
            <div class="qp-choice-icon"><?= $icon ?: $label ?></div>
            <span><?= htmlspecialchars($option) ?></span>
          </div>
          <?php endforeach; ?>

        <?php else: ?>
          <?php $correctAnsVal = $_GET['correctAns'] ?? ''; $ansVal = $_GET['ans'] ?? '';
                foreach ($choices as $key => $value):
                  $num = $key + 1;
                  $isCorrectChoice  = ($correctAnsVal == $num);
                  $isSelectedChoice = ($ansVal == $num);
                  $cls = $isCorrectChoice ? 'correct' : ($isSelectedChoice ? 'selected-wrong' : 'neutral');
                  $icon = $isCorrectChoice ? '<i class="bi bi-check-lg"></i>' : ($isSelectedChoice ? '<i class="bi bi-x-lg"></i>' : chr(64 + $num));
          ?>
          <div class="qp-choice <?= $cls ?>">
            <div class="qp-choice-icon"><?= $icon ?></div>
            <span><?= htmlspecialchars($value) ?></span>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <!-- Tags -->
        <div class="qp-tags">
          <div class="qp-tag-item">Subject: <strong><?= htmlspecialchars($topics1) ?></strong></div>
          <div class="qp-tag-item">Time Taken: <strong><?= htmlspecialchars($timeTaken) ?> sec</strong></div>
          <div class="qp-tag-item">Difficulty: <strong><?= htmlspecialchars($dlevel) ?></strong></div>
          <div class="qp-tag-item">System: <strong><?= htmlspecialchars($system) ?></strong></div>
          <div class="qp-tag-item">Client Needs: <strong><?= htmlspecialchars($cnc) ?></strong></div>
          <div class="qp-tag-item">Question ID: <strong><?= str_pad(htmlspecialchars($questionId), 5, '0', STR_PAD_LEFT) ?></strong></div>
        </div>
      </div>
    </div>

    <!-- Right: Rationale + NARC Notes -->
    <div class="col-lg-6">
      <div class="qp-card">
        <div class="qp-q-number"><i class="bi bi-lightbulb-fill me-1"></i> Rationale</div>
        <div class="qp-rationale-text"><?= nl2br(htmlspecialchars(str_replace('\n', "\n", $rationale))) ?></div>

        <?php if (!empty($narcan)): ?>
        <div class="qp-narc-box">
          <div class="qp-narc-title"><i class="bi bi-journal-bookmark-fill"></i> NARC Additional Notes</div>
          <div class="qp-narc-body"><?= nl2br(htmlspecialchars(str_replace('\n', "\n", $narcan))) ?></div>
        </div>
        <?php endif; ?>
      </div>
    </div>

  </div>
</div>

</body>
</html>
