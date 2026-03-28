<?php
include '../../../../config.php';
session_start();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch question from traditional table
if ($id > 0) {
    $q = mysqli_query($con, "SELECT * FROM traditional WHERE id = $id LIMIT 1");
} else {
    $q = mysqli_query($con, "SELECT * FROM traditional ORDER BY RAND() LIMIT 1");
}

$data = mysqli_fetch_assoc($q);

if (!$data) {
    die('<div style="font-family: Inter, sans-serif; padding: 24px;">No multiple choice question found.</div>');
}

// Parse question data
$questionText = $data['question'] ?? '';

// Decode choices from JSON
$choicesJSON = $data['choices'] ?? '[]';
$choicesArray = json_decode($choicesJSON, true) ?? [];

// Handle correct answer - convert letter (A/B/C/D) to index (0/1/2/3) if needed
$correctAns = $data['correct'] ?? 'A';
$correctAnswer = 1; // default
if (is_string($correctAns)) {
    $letterToIndex = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4];
    $correctAnswer = $letterToIndex[$correctAns] ?? 1;
} else {
    $correctAnswer = intval($correctAns);
}

$rationale = $data['rationale'] ?? '';
$topic = $data['topic'] ?? 'General';
$system = $data['system'] ?? 'N/A';
$cnc = $data['cnc'] ?? 'N/A';
$dlevel = $data['dlevel'] ?? 'N/A';

// Build options array with proper labels
$options = [];
$labels = ['A', 'B', 'C', 'D'];
foreach ($choicesArray as $idx => $choice) {
    $options[] = [
        'value' => $idx + 1,
        'text' => $choice,
        'label' => $labels[$idx] ?? chr(65 + $idx)
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Multiple Choice Question</title>
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
      background: #eef2ff;
      color: #4338ca;
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

    .options-container {
      display: flex;
      flex-direction: column;
      gap: 12px;
      margin-bottom: 28px;
    }

    .option-item {
      display: flex;
      align-items: center;
      gap: 16px;
      padding: 16px 20px;
      border: 2px solid #e5e7eb;
      border-radius: 12px;
      background: #ffffff;
      cursor: pointer;
      transition: all 0.2s ease;
      position: relative;
    }

    .option-item:hover {
      border-color: var(--accent);
      background: #f0f7ff;
      box-shadow: 0 2px 8px rgba(59, 130, 246, 0.08);
    }

    .option-item input[type="radio"]:checked + .option-label + .option-text,
    .option-item input[type="radio"]:checked ~ .option-label {
      /* Used to trigger state */
    }

    .option-item input[type="radio"] {
      display: none;
    }

    .option-item input[type="radio"]:disabled {
      opacity: 0.6;
      cursor: not-allowed;
    }

    .option-label {
      background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
      color: var(--primary);
      font-weight: 800;
      min-width: 44px;
      height: 44px;
      display: flex;
      align-items: center;
      justify-content: center;
      border-radius: 10px;
      font-size: 15px;
      flex-shrink: 0;
      transition: all 0.2s ease;
    }

    .option-text {
      flex: 1;
      font-size: 16px;
      font-weight: 500;
      color: var(--text);
      line-height: 1.5;
    }

    /* Selected/Focused state */
    .option-item input[type="radio"]:checked ~ .option-label {
      background: linear-gradient(135deg, var(--accent) 0%, #2563eb 100%);
      color: white;
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.2);
      transform: none;
    }

    .option-item.selected {
      border-color: var(--accent);
      background: #eff6ff;
      box-shadow: none;
    }

    /* Feedback states */
    .option-item.correct-reveal {
      border-color: var(--success);
      background: #f0fdf4;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.1);
    }

    .option-item.correct-reveal .option-label {
      background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }

    .option-item.correct-reveal .option-text {
      text-decoration: none;
    }

    /* Omitted answer - the one they initially selected but changed */
    .option-item.omitted-reveal {
      border-color: #f59e0b;
      background: #fffbeb;
      box-shadow: 0 4px 12px rgba(245, 158, 11, 0.1);
      opacity: 0.75;
    }

    .option-item.omitted-reveal .option-label {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
    }

    .option-item.omitted-reveal .option-text {
      text-decoration: line-through;
      color: #92400e;
    }

    .option-item.wrong-reveal {
      border-color: var(--danger);
      background: #fef2f2;
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.1);
    }

    .option-item.wrong-reveal .option-label {
      background: linear-gradient(135deg, var(--danger) 0%, #dc2626 100%);
      color: white;
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
    }

    .option-item.disabled {
      opacity: 0.5;
      pointer-events: none;
    }

    /* Icons for feedback */
    .option-icon {
      font-size: 18px;
      margin-left: 8px;
      flex-shrink: 0;
    }

    .option-icon.correct {
      color: var(--success);
    }

    .option-icon.wrong {
      color: var(--danger);
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

      .option-item {
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

      .option-item {
        padding: 12px 12px;
        gap: 10px;
      }

      .option-text {
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
    <i class="fas fa-circle-check"></i> SELECT ONE ANSWER
  </div>

  <div class="question-header">
    <?= nl2br(htmlspecialchars($questionText)) ?>
  </div>

  <form id="mcForm">
    <div class="options-container">
      <?php foreach($options as $idx => $opt): ?>
      <label class="option-item" data-value="<?= $opt['value'] ?>">
        <input type="radio" name="answer" value="<?= $opt['value'] ?>" required>
        <span class="option-label"><?= $opt['label'] ?></span>
        <span class="option-text"><?= nl2br(htmlspecialchars($opt['text'])) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
  </form>

  <div class="result-section" id="resultSection">
    <div class="result-title" id="resultTitle"></div>
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
      <i class="fas fa-check-circle"></i> Submit Answer
    </button>
  </div>
</div>

<script>
  const correctAnswer = <?= json_encode($correctAnswer) ?>;
  const rationale = <?= json_encode($rationale) ?>;
  const questionId = <?= json_encode($id) ?>;

  let locked = false;
  let isReviewMode = false;  // Flag for read-only review mode
  let userAnswer = null;
  let initialAnswer = null;  // Track first answer selected
  let answerChanged = false; // Flag if answer was changed
  let hasInteracted = false; // Track first user interaction
  let changes = null;        // Track what changed

  // ===== PREFILL MESSAGE HANDLER (for review/resume) =====
  window.addEventListener('message', (event) => {
    if (!event.data || event.data.type !== 'prefill') return;

    const prefillData = event.data;
    const showRationale = prefillData.showRationale ?? false;
    isReviewMode = prefillData.isReview ?? false;

    // If we have a previous answer, populate and lock
    if (prefillData.answer !== undefined && prefillData.answer !== null) {
      const prevAnswer = parseInt(prefillData.answer);
      
      // Set the radio button
      const radio = document.querySelector(`input[name="answer"][value="${prevAnswer}"]`);
      if (radio) {
        radio.checked = true;
        userAnswer = prevAnswer;
        // Use initial_answer if provided, otherwise use current answer
        initialAnswer = (prefillData.initial_answer !== undefined) ? prefillData.initial_answer : prevAnswer;
        
        // Mark as selected in UI
        document.querySelectorAll('.option-item').forEach(item => {
          item.classList.remove('selected');
        });
        radio.closest('.option-item').classList.add('selected');
      }

      // If in review mode, show feedback and lock
      if (showRationale) {
        revealFeedback(prefillData);
        setReadOnlyState();
      }
    }
  });

  // Handle option selection
  document.querySelectorAll('input[name="answer"]').forEach(input => {
    input.addEventListener('change', function() {
      if (locked || isReviewMode) return; // Prevent changes in review mode
      
      // Capture initial on first interaction
      if(!hasInteracted) {
        hasInteracted = true;
        initialAnswer = userAnswer;
      }
      
      const newAnswer = parseInt(this.value);
      
      // Track initial answer on first selection
      if (initialAnswer === null) {
        initialAnswer = newAnswer;
      } else if (newAnswer !== initialAnswer) {
        // Answer was changed - track the change
        answerChanged = true;
      }
      
      userAnswer = newAnswer;
      
      // Update UI
      document.querySelectorAll('.option-item').forEach(item => {
        item.classList.remove('selected');
      });
      this.closest('.option-item').classList.add('selected');
    });
  });

  // Submit button
  document.getElementById('submitBtn').addEventListener('click', function() {
    if (locked || isReviewMode) return; // Prevent resubmission in review mode
    
    if (!userAnswer) {
      Swal.fire({
        icon: 'warning',
        title: 'Please Select Answer',
        text: 'You must select an answer before submitting.',
        confirmButtonColor: '#3b82f6'
      });
      return;
    }

    revealFeedback();
    setReadOnlyState();
    sendAnswerToParent();
  });

  function setReadOnlyState() {
    // Disable all radio buttons
    document.querySelectorAll('input[name="answer"]').forEach(input => {
      input.disabled = true;
    });
    // Hide submit button
    document.getElementById('submitBtn').style.display = 'none';
    locked = true;
  }

  function revealFeedback(prefillData = null) {
    const isCorrect = userAnswer === correctAnswer;
    
    document.querySelectorAll('.option-item').forEach((item, idx) => {
      const value = parseInt(item.dataset.value);
      item.classList.remove('correct-reveal', 'wrong-reveal', 'disabled', 'omitted-reveal');
      item.querySelector('input').disabled = true;

      // Show omitted answer if it exists and is different from final answer
      const showOmitted = (prefillData?.initial_answer !== undefined && prefillData?.initial_answer !== null) ? 
        (value === parseInt(prefillData.initial_answer) && value !== userAnswer) :
        (answerChanged && value === initialAnswer && value !== userAnswer);
        
      if (showOmitted) {
        item.classList.add('omitted-reveal');
      } else if (value === correctAnswer) {
        item.classList.add('correct-reveal');
      } else if (value === userAnswer && !isCorrect) {
        item.classList.add('wrong-reveal');
      } else if (value !== correctAnswer && value !== userAnswer) {
        item.classList.add('disabled');
      }
    });

    // Show result section
    const resultSection = document.getElementById('resultSection');
    const resultTitle = document.getElementById('resultTitle');
    const rationaleContent = document.getElementById('rationaleContent');

    if (isCorrect) {
      resultTitle.className = 'result-title correct';
      resultTitle.innerHTML = '<i class="fas fa-check-circle"></i> Correct!';
    } else {
      resultTitle.className = 'result-title incorrect';
      resultTitle.innerHTML = '<i class="fas fa-times-circle"></i> Incorrect';
    }

    // Add omitted answer note if applicable
    let rationaleText = rationale ? '<strong>Rationale:</strong> ' + rationale.replace(/\n/g, '<br>') : 'No rationale provided.';
    
    const hasChanges = (prefillData?.initial_answer !== undefined && prefillData?.initial_answer !== null) ? 
      (parseInt(prefillData.initial_answer) !== userAnswer) :
      (answerChanged && initialAnswer !== null);
    
    if (hasChanges) {
      const labelMap = {1: 'A', 2: 'B', 3: 'C', 4: 'D'};
      const omittedVal = (prefillData?.initial_answer !== undefined) ? parseInt(prefillData.initial_answer) : initialAnswer;
      rationaleText += '<br><br><span style="color: #f59e0b; font-weight: 600;"><i class="fas fa-info-circle"></i> You changed your answer from <strong>' + labelMap[omittedVal] + '</strong> to <strong>' + labelMap[userAnswer] + '</strong></span>';
    }
    
    rationaleContent.innerHTML = rationaleText;

    resultSection.classList.add('show');
  }

  function sendAnswerToParent() {
    // Calculate changes for JSON storage
    let changesData = null;
    if (answerChanged && initialAnswer !== null) {
      changesData = {
        added: [userAnswer],
        removed: [initialAnswer],
        modified_count: 1,
        changed: true
      };
    }

    if (window.parent !== window) {
      window.parent.postMessage({
        type: 'answered',
        answer: userAnswer,
        initial_answer: answerChanged ? initialAnswer : null,
        correctAnswer: correctAnswer,
        correct: userAnswer === correctAnswer,
        score: userAnswer === correctAnswer ? 1 : 0,
        maxPoints: 1,
        earned_points: userAnswer === correctAnswer ? 1 : 0,
        max_points: 1,
        changes: changesData,
        rationale: rationale,
        topic: <?= json_encode($topic) ?>,
        system: <?= json_encode($system) ?>,
        cnc: <?= json_encode($cnc) ?>,
        dlevel: <?= json_encode($dlevel) ?>,
        question_id: questionId,
        question_type: 'traditional'
      }, '*');
    }
  }
</script>

</body>
</html>
