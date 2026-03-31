<?php
// sata/index.php - Select All That Apply Question

require_once '../../../../config.php';
// session_start handled by config.php

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch question from sata table
if ($id > 0) {
    $q = mysqli_query($con, "SELECT * FROM sata WHERE id = $id LIMIT 1");
} else {
    $q = mysqli_query($con, "SELECT * FROM sata ORDER BY RAND() LIMIT 1");
}

$data = mysqli_fetch_assoc($q);

if (!$data) {
    die('<div style="font-family: Inter, sans-serif; padding: 24px;">No SATA question found.</div>');
}

// Parse question data
$questionText = $data['question'] ?? '';
$items = json_decode($data['items'], true) ?? [];
$correctAnswers = json_decode($data['correct'], true) ?? [];
$rationale = $data['rationale'] ?? '';
$topic = $data['topic'] ?? 'General';
$system = $data['system'] ?? 'N/A';
$cnc = $data['cnc'] ?? 'N/A';
$dlevel = $data['dlevel'] ?? 'N/A';

// Ensure items is an array of strings
$items = array_map(function($item) {
    return is_array($item) ? ($item['text'] ?? $item) : $item;
}, $items);

// Ensure correct answers is an array of strings
$correctAnswers = array_map('strval', (array)$correctAnswers);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SATA Question - Select All That Apply</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <style>
    :root {
      --primary: #0a1628;
      --accent: #3b82f6;
      --success: #10b981;
      --danger: #ef4444;
      --surface: #ffffff;
      --border: #e2e8f0;
      --text: #0f172a;
      --text-muted: #64748b;
      --bg-soft: #f8fafc;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Inter', sans-serif;
      background: transparent;
      color: var(--text);
      padding: 24px;
    }

    .card {
      max-width: 950px;
      margin: 0 auto;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 32px;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
    }

    .previous-badge {
      display: none;
      background: #f1f5f9;
      color: #475569;
      font-size: 12px;
      font-weight: 600;
      padding: 10px 16px;
      border-radius: 8px;
      margin-bottom: 20px;
      border-left: 4px solid #cbd5e1;
    }

    .instruction-badge {
      display: inline-block;
      background: #fef3c7;
      color: #92400e;
      font-size: 11px;
      font-weight: 800;
      letter-spacing: 0.6px;
      text-transform: uppercase;
      border-radius: 999px;
      padding: 6px 14px;
      margin-bottom: 16px;
    }

    .question-header {
      font-size: 18px;
      line-height: 1.7;
      color: var(--primary);
      margin-bottom: 28px;
      font-weight: 600;
    }

    .items-container {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 28px;
    }

    .item-checkbox {
      display: flex;
      align-items: flex-start;
      gap: 14px;
      padding: 16px 18px;
      border: 2px solid var(--border);
      border-radius: 12px;
      background: white;
      cursor: pointer;
      transition: all 0.3s ease;
      position: relative;
    }

    .item-checkbox:hover {
      border-color: var(--accent);
      background: #f8fafc;
    }

    .item-checkbox input[type="checkbox"] {
      margin-top: 4px;
      cursor: pointer;
      width: 20px;
      height: 20px;
      flex-shrink: 0;
      accent-color: var(--accent);
    }

    .item-checkbox input[type="checkbox"]:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .item-text {
      flex: 1;
      font-size: 15px;
      font-weight: 500;
      color: var(--text);
      line-height: 1.6;
    }

    /* Selected state */
    .item-checkbox input[type="checkbox"]:checked ~ .item-text {
      color: var(--accent);
      font-weight: 600;
    }

    .item-checkbox.selected {
      border-color: var(--accent);
      background: #eff6ff;
    }

    /* Feedback states */
    .item-checkbox.correct-reveal {
      border-color: var(--success);
      background: #f0fdf4;
    }

    .item-checkbox.wrong-reveal {
      border-color: var(--danger);
      background: #fef2f2;
    }

    /* Omitted answer - item was checked but later unchecked */
    .item-checkbox.omitted-reveal {
      border-color: #f59e0b;
      background: #fffbeb;
      opacity: 0.75;
    }

    .item-checkbox.omitted-reveal .checkbox-label {
      text-decoration: line-through;
      color: #92400e;
    }

    .item-checkbox.disabled {
      opacity: 0.6;
      pointer-events: none;
    }

    .item-icon {
      font-size: 16px;
      margin-left: 8px;
      flex-shrink: 0;
    }

    .item-icon.correct {
      color: var(--success);
    }

    .item-icon.wrong {
      color: var(--danger);
    }

    .selection-count {
      font-size: 12px;
      color: var(--text-muted);
      margin-bottom: 16px;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.3px;
    }

    .result-section {
      display: none;
      margin-top: 28px;
      padding: 20px 24px;
      background: #f8fafc;
      border-left: 4px solid var(--accent);
      border-radius: 10px;
    }

    .result-section.show {
      display: block;
      animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
      from {
        opacity: 0;
        transform: translateY(-10px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .result-title {
      font-size: 14px;
      font-weight: 800;
      text-transform: uppercase;
      margin-bottom: 8px;
      letter-spacing: 0.5px;
    }

    .result-title.correct {
      color: var(--success);
    }

    .result-title.incorrect {
      color: var(--danger);
    }

    .result-score {
      font-size: 13px;
      color: var(--text-muted);
      margin-top: 8px;
      font-weight: 600;
    }

    .rationale-content {
      font-size: 14px;
      line-height: 1.7;
      color: var(--text-muted);
      margin-top: 12px;
    }

    .details-section {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr 1fr;
      gap: 12px;
      margin-top: 12px;
      padding-top: 12px;
      border-top: 1px solid var(--border);
      font-size: 12px;
    }

    .detail-item {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }

    .detail-label {
      font-weight: 700;
      color: var(--text-muted);
      text-transform: uppercase;
      letter-spacing: 0.3px;
      font-size: 10px;
    }

    .detail-value {
      color: var(--primary);
      font-weight: 600;
    }

    .button-group {
      display: flex;
      gap: 12px;
      justify-content: flex-end;
      margin-top: 28px;
    }

    .btn {
      padding: 12px 28px;
      border-radius: 10px;
      font-weight: 700;
      font-size: 14px;
      cursor: pointer;
      border: none;
      transition: all 0.2s ease;
    }

    .btn-primary {
      background: var(--primary);
      color: white;
    }

    .btn-primary:hover:not(:disabled) {
      background: #132747;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(10, 22, 40, 0.2);
    }

    .btn-primary:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    .btn-outline {
      background: transparent;
      border: 2px solid var(--border);
      color: var(--text-muted);
    }

    .btn-outline:hover {
      border-color: var(--accent);
      color: var(--accent);
    }

    @media (max-width: 768px) {
      .card {
        padding: 20px;
      }

      .question-header {
        font-size: 16px;
        margin-bottom: 20px;
      }

      .item-checkbox {
        padding: 14px 14px;
        gap: 12px;
      }

      .details-section {
        grid-template-columns: 1fr 1fr;
        gap: 10px;
      }

      .button-group {
        flex-direction: stretch;
        gap: 10px;
      }

      .btn {
        flex: 1;
        padding: 12px 16px;
      }
    }

    @media (max-width: 480px) {
      .card {
        padding: 16px;
      }

      .question-header {
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 16px;
      }

      .item-checkbox {
        padding: 12px 12px;
        gap: 10px;
      }

      .item-text {
        font-size: 14px;
      }

      .instruction-badge {
        font-size: 10px;
        padding: 5px 10px;
      }

      .details-section {
        grid-template-columns: 1fr;
        gap: 8px;
      }
    }
  </style>
</head>
<body>

<div class="card">

  <div class="instruction-badge">
    <i class="fas fa-asterisk"></i> SELECT ALL THAT APPLY
  </div>

  <div class="question-header">
    <?= nl2br(htmlspecialchars($questionText)) ?>
  </div>

  <div class="selection-count" id="selectionCount">Select correct answers</div>

  <form id="sataForm">
    <div class="items-container">
      <?php foreach($items as $idx => $item): ?>
      <label class="item-checkbox" data-index="<?= $idx ?>" data-value="<?= htmlspecialchars($item) ?>">
        <input type="checkbox" name="answers" value="<?= htmlspecialchars($item) ?>">
        <span class="item-text"><?= nl2br(htmlspecialchars($item)) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </form>

  <div class="result-section" id="resultSection">
    <div class="result-title" id="resultTitle"></div>
    <div class="result-score" id="resultScore"></div>
    <div class="rationale-content" id="rationaleContent"></div>
    <div class="details-section">
      <div class="detail-item">
        <span class="detail-label">Topic</span>
        <span class="detail-value"><?= htmlspecialchars($topic) ?></span>
      </div>
      <div class="detail-item">
        <span class="detail-label">System</span>
        <span class="detail-value"><?= htmlspecialchars($system) ?></span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Client Needs</span>
        <span class="detail-value"><?= htmlspecialchars($cnc) ?></span>
      </div>
      <div class="detail-item">
        <span class="detail-label">Difficulty</span>
        <span class="detail-value"><?= htmlspecialchars($dlevel) ?></span>
      </div>
    </div>
  </div>

  <div class="button-group">
    <button type="button" class="btn btn-primary" id="submitBtn">
      <i class="fas fa-check-circle"></i> Submit Answers
    </button>
  </div>
</div>

<script>
  const correctAnswers = <?= json_encode($correctAnswers) ?>;
  const rationale = <?= json_encode($rationale) ?>;
  const questionId = <?= json_encode($data['id'] ?? 0) ?>;
  const totalCorrect = correctAnswers.length;

  let locked = false;
  let isReviewMode = false;
  let userAnswers = [];
  let initialAnswers = [];  // Track initial selections
  let hasInteracted = false; // Track first user interaction
  let changes = null;       // Track what changed

  // ===== PREFILL MESSAGE HANDLER (for review/resume) =====
  window.addEventListener('message', (event) => {
    if (!event.data || event.data.type !== 'prefill') return;

    const prefillData = event.data;
    const showRationale = prefillData.showRationale ?? false;
    isReviewMode = prefillData.isReview ?? false;

    // If we have a previous answer, populate and lock
    if (prefillData.answer !== undefined && prefillData.answer !== null && Array.isArray(prefillData.answer)) {
      userAnswers = prefillData.answer.map(String);
      // Use initial_answer if provided, otherwise use current answer
      initialAnswers = (prefillData.initial_answer && prefillData.initial_answer.map) ? prefillData.initial_answer.map(String) : userAnswers.slice();

      // Check all previously selected boxes
      document.querySelectorAll('input[name="answers"]').forEach(checkbox => {
        if (userAnswers.includes(checkbox.value)) {
          checkbox.checked = true;
          checkbox.closest('.item-checkbox').classList.add('selected');
        }
      });

      updateSelectionCount();

      // If in review mode, show feedback and lock
      if (showRationale) {
        revealFeedback(prefillData);
        setReadOnlyState();
      }
    }
  });

  // Handle checkbox changes
  document.querySelectorAll('input[name="answers"]').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      if (locked || isReviewMode) return; // Prevent changes in review mode
      
      // Capture initial on first interaction
      if(!hasInteracted) {
        hasInteracted = true;
        const current = Array.from(document.querySelectorAll('input[name="answers"]:checked')).map(c => c.value);
        initialAnswers = [...current];
      }
      
      updateSelectionCount();
      const newAnswers = Array.from(document.querySelectorAll('input[name="answers"]:checked')).map(c => c.value);
      
      // Calculate changes
      const added = newAnswers.filter(a => !userAnswers.includes(a));
      const removed = userAnswers.filter(a => !newAnswers.includes(a));
      
      if (added.length > 0 || removed.length > 0) {
        changes = {
          added: added,
          removed: removed,
          modified_count: added.length + removed.length,
          changed: true
        };
      }
      
      userAnswers = newAnswers;
      
      // Update selected state
      document.querySelectorAll('.item-checkbox').forEach(item => {
        const input = item.querySelector('input');
        if (input.checked) {
          item.classList.add('selected');
        } else {
          item.classList.remove('selected');
        }
      });
    });
  });

  function updateSelectionCount() {
    const count = document.querySelectorAll('input[name="answers"]:checked').length;
    const countElement = document.getElementById('selectionCount');
    if (count === 0) {
      countElement.textContent = 'Select correct answers';
    } else {
      countElement.textContent = `${count} of ${totalCorrect} selected`;
    }
  }

  // Submit button
  document.getElementById('submitBtn').addEventListener('click', function() {
    if (locked || isReviewMode) return; // Prevent resubmission in review mode
    
    userAnswers = Array.from(document.querySelectorAll('input[name="answers"]:checked')).map(c => c.value);
    
    if (userAnswers.length === 0) {
      Swal.fire({
        icon: 'warning',
        title: 'Please Select Answers',
        text: 'You must select at least one answer before submitting.',
        confirmButtonColor: '#3b82f6'
      });
      return;
    }

    revealFeedback();
    setReadOnlyState();
    sendAnswerToParent();
  });

  function setReadOnlyState() {
    // Disable all checkboxes
    document.querySelectorAll('input[name="answers"]').forEach(input => {
      input.disabled = true;
    });
    // Hide submit button
    document.getElementById('submitBtn').style.display = 'none';
    locked = true;
  }

  function revealFeedback(prefillData = null) {
    // Check results
    let correctCount = 0;
    let incorrectCount = 0;

    userAnswers.forEach(answer => {
      if (correctAnswers.includes(answer)) {
        correctCount++;
      } else {
        incorrectCount++;
      }
    });

    // Check for missed correct answers
    let missedCorrect = 0;
    correctAnswers.forEach(correct => {
      if (!userAnswers.includes(correct)) {
        missedCorrect++;
      }
    });

    // Perfect means all correct selected and no wrong selected
    const isPerfect = correctCount === totalCorrect && incorrectCount === 0;

    // Determine initial answers for display
    const displayInitialAnswers = prefillData?.initial_answer ? prefillData.initial_answer : initialAnswers;

    // Update UI
    document.querySelectorAll('.item-checkbox').forEach((item) => {
      const value = item.getAttribute('data-value');
      const input = item.querySelector('input');
      
      item.classList.remove('correct-reveal', 'wrong-reveal', 'disabled', 'omitted-reveal');
      input.disabled = true;

      // Show omitted items (were selected but later unselected)
      if (displayInitialAnswers.includes(value) && !userAnswers.includes(value)) {
        item.classList.add('omitted-reveal');
      } else if (correctAnswers.includes(value)) {
        item.classList.add('correct-reveal');
      } else if (userAnswers.includes(value) && !correctAnswers.includes(value)) {
        item.classList.add('wrong-reveal');
      } else {
        item.classList.add('disabled');
      }
    });

    // Show result section
    const resultSection = document.getElementById('resultSection');
    const resultTitle = document.getElementById('resultTitle');
    const resultScore = document.getElementById('resultScore');
    const rationaleContent = document.getElementById('rationaleContent');

    if (isPerfect) {
      resultTitle.className = 'result-title correct';
      resultTitle.innerHTML = '<i class="fas fa-check-circle"></i> Correct!';
    } else {
      resultTitle.className = 'result-title incorrect';
      resultTitle.innerHTML = '<i class="fas fa-times-circle"></i> Incomplete or Incorrect';
    }

    resultScore.textContent = `Selected: ${userAnswers.length} | Correct: ${correctCount} / ${totalCorrect}`;
    
    let rationaleText = rationale ? '<strong>Rationale:</strong> ' + rationale.replace(/\n/g, '<br>') : 'No rationale provided.';
    
    // Add omitted items summary if any
    const hasOmitted = displayInitialAnswers.some(a => !userAnswers.includes(a));
    if (hasOmitted) {
      rationaleText += '<br><br><span style="color: #f59e0b; font-weight: 600;"><i class="fas fa-info-circle"></i> You deselected some items</span>';
    }
    
    rationaleContent.innerHTML = rationaleText;

    resultSection.classList.add('show');
  }

  function sendAnswerToParent() {
    let isCorrect = false;
    let correctCount = 0;

    userAnswers.forEach(answer => {
      if (correctAnswers.includes(answer)) {
        correctCount++;
      }
    });

    let missedCount = 0;
    correctAnswers.forEach(correct => {
      if (!userAnswers.includes(correct)) {
        missedCount++;
      }
    });

    isCorrect = (correctCount === totalCorrect && missedCount === 0);

    // Build changes data
    let changesData = null;
    if (initialAnswers.length > 0 && initialAnswers.length !== userAnswers.length) {
      const added = userAnswers.filter(a => !initialAnswers.includes(a));
      const removed = initialAnswers.filter(a => !userAnswers.includes(a));
      
      if (added.length > 0 || removed.length > 0) {
        changesData = {
          added: added,
          removed: removed,
          modified_count: added.length + removed.length,
          changed: true
        };
      }
    }

    if (window.parent !== window) {
      window.parent.postMessage({
        type: 'answered',
        answer: userAnswers,
        initial_answer: initialAnswers.length > 0 ? initialAnswers : null,
        correctAnswer: correctAnswers,
        correct: isCorrect,
        score: isCorrect ? 1 : (correctCount / totalCorrect),
        maxPoints: 1,
        earned_points: correctCount,
        max_points: totalCorrect,
        changes: changesData,
        rationale: rationale,
        topic: <?= json_encode($topic) ?>,
        system: <?= json_encode($system) ?>,
        cnc: <?= json_encode($cnc) ?>,
        dlevel: <?= json_encode($dlevel) ?>,
        question_id: questionId,
        question_type: 'sata'
      }, '*');
    }
  }
</script>

</body>
</html>