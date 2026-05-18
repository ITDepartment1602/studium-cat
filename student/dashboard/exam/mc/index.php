<?php
require_once '../../../../config.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Whitelist: mcq uses choices JSON + correct; cat uses choiceA/B/C/D + correctans
$allowedSrc = ['mcq', 'cat'];
$src = (isset($_GET['src']) && in_array($_GET['src'], $allowedSrc, true)) ? $_GET['src'] : 'mcq';

if ($id > 0) {
    $q = mysqli_query($con, "SELECT * FROM `{$src}` WHERE id = " . $id . " LIMIT 1");
} else {
    $filter = ($src === 'cat') ? "WHERE (type IS NULL OR type = '')" : "";
    $q = mysqli_query($con, "SELECT * FROM `{$src}` {$filter} ORDER BY RAND() LIMIT 1");
}

if (!$q) {
    die('<div style="font-family: Inter, sans-serif; padding: 24px;">Database error: ' . htmlspecialchars(mysqli_error($con)) . '</div>');
}

$data = mysqli_fetch_assoc($q);

if (!$data) {
    die('<div style="font-family: Inter, sans-serif; padding: 24px;">No multiple choice question found.</div>');
}

// ── Normalize field differences between mcq and cat ──────────────────────────
$questionText = $data['question'] ?? '';
$rationale    = $data['rationale'] ?? '';
$topic        = $data['topic']     ?? 'General';
$system       = $data['system']    ?? 'N/A';
$cnc          = $data['cnc']       ?? 'N/A';
$dlevel       = $data['dlevel']    ?? 'N/A';

if ($src === 'cat') {
    // cat: separate choiceA–D columns, correct answer in `correctans`
    $choicesArray = array_filter([
        $data['choiceA'] ?? null,
        $data['choiceB'] ?? null,
        $data['choiceC'] ?? null,
        $data['choiceD'] ?? null,
    ], fn($v) => $v !== null && $v !== '');
    $choicesArray = array_values($choicesArray);
    $rawCorrect   = strtoupper(trim($data['correctans'] ?? 'A'));
} else {
    // mcq: choices as JSON array, correct answer in `correct`
    $choicesArray = json_decode($data['choices'] ?? '[]', true) ?? [];
    $rawCorrect   = strtoupper(trim($data['correct'] ?? 'A'));
}

// Map letter → 1-based index (A→1, B→2, …) or use numeric value directly
$letterToIndex = ['A' => 1, 'B' => 2, 'C' => 3, 'D' => 4];
$correctAnswer = $letterToIndex[$rawCorrect] ?? (is_numeric($rawCorrect) ? intval($rawCorrect) : 1);

// Dynamic clinical reference tabs — only mcq table has `tabs`
$tabs_data = ($src === 'mcq') ? (json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: []) : [];
$hasTabs   = !empty($tabs_data);

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
    }

    /* Two-panel layout */
    .two-panel { display: flex; min-height: 100vh; overflow: hidden; }
    .left-panel { width: 40%; min-width: 260px; background: #fff; border-right: 2px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; overflow: hidden; }
    .panel-title { padding: 14px 20px; background: #f1f5f9; font-weight: 800; font-size: 11px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; border-bottom: 1px solid var(--border); }
    .tabs-row { display: flex; padding: 8px 12px 0; gap: 4px; border-bottom: 1px solid var(--border); overflow-x: auto; flex-shrink: 0; }
    .tab-btn { padding: 9px 14px; font-size: 13px; font-weight: 600; cursor: pointer; border-radius: 8px 8px 0 0; color: var(--text-muted); white-space: nowrap; }
    .tab-btn.active { background: #f8fafc; color: var(--accent); border: 1px solid var(--border); border-bottom-color: #f8fafc; margin-bottom: -1px; }
    .tab-content-area { flex: 1; overflow-y: auto; padding: 16px; }
    .clinical-record { background: #fdfdfd; border: 1px solid #f1f5f9; padding: 10px 14px; border-radius: 8px; margin-bottom: 8px; font-size: 14px; line-height: 1.5; }
    .right-panel { flex: 1; overflow-y: auto; padding: 24px; min-width: 0; }
    @media (max-width: 900px) {
      .two-panel { flex-direction: column; height: auto; overflow: visible; }
      .left-panel { width: 100%; min-width: 0; border-right: none; border-bottom: 2px solid var(--border); max-height: 35vh; overflow-y: auto; }
      .right-panel { width: 100% !important; overflow: visible; }
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
      position: sticky;
      bottom: 0;
      background: #fff;
      padding: 16px 0 20px;
      margin-top: 24px;
      border-top: 1px solid var(--border);
      display: flex;
      justify-content: center;
      z-index: 10;
    }

    .btn {
      padding: 14px 40px;
      border-radius: 12px;
      font-weight: 800;
      font-size: 15px;
      cursor: pointer;
      border: none;
      transition: all 0.2s ease;
      min-width: 220px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 10px;
    }

    .btn-primary {
      background: linear-gradient(135deg, #3b82f6, #1d4ed8);
      color: white;
      box-shadow: 0 4px 14px rgba(59, 130, 246, 0.4);
    }

    .btn-primary:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(59, 130, 246, 0.5);
    }

    .btn-primary:disabled {
      opacity: 0.45;
      cursor: not-allowed;
      transform: none !important;
      box-shadow: none;
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
<div class="two-panel">
<?php if ($hasTabs): ?>
<div class="left-panel">
  <div class="panel-title">Clinical Reference</div>
  <div class="tabs-row">
    <?php foreach ($tabs_data as $i => $tab): ?>
    <div class="tab-btn <?= $i === 0 ? 'active' : '' ?>" data-tab="ttab-<?= $i ?>"><?= htmlspecialchars($tab['title']) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="tab-content-area">
    <?php foreach ($tabs_data as $i => $tab): ?>
    <div id="ttab-<?= $i ?>" class="tab-pane" <?= $i > 0 ? 'style="display:none;"' : '' ?>>
      <?php foreach ((array)($tab['content'] ?? []) as $item): ?>
      <div class="clinical-record"><?= htmlspecialchars($item) ?></div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
<div class="right-panel" <?= !$hasTabs ? 'style="width:100%;"' : '' ?>>

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

  <div class="button-group">
    <button type="button" class="btn btn-primary" id="submitBtn" disabled>
      <i class="fas fa-check-circle"></i> Submit Answer
    </button>
  </div>
</div>
</div><!-- /.right-panel -->
</div><!-- /.two-panel -->

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    document.querySelectorAll('.tab-pane').forEach(p => p.style.display = 'none');
    document.getElementById(this.dataset.tab).style.display = '';
  });
});

  const correctAnswer = <?= json_encode($correctAnswer) ?>;
  const rationale     = <?= json_encode($rationale) ?>;
  const questionId    = <?= json_encode($id) ?>;

  let locked        = false;
  let userAnswer    = null;
  let initialAnswer = null;
  let answerChanged = false;

  // Signal parent that this iframe is ready
  if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');

  // Timeout from parent (Pressure Mode): lock UI
  window.addEventListener('message', e => {
    if (e.data?.type !== 'timeout') return;
    setReadOnlyState();
    document.getElementById('submitBtn').style.display = 'none';
    // Show time-expired overlay
    const ov = document.createElement('div');
    ov.style.cssText = 'position:fixed;inset:0;background:rgba(239,68,68,.08);display:flex;align-items:flex-start;justify-content:center;padding-top:20px;z-index:9999;pointer-events:none;';
    ov.innerHTML = '<div style="background:#ef4444;color:#fff;padding:8px 22px;border-radius:100px;font-weight:800;font-size:13px;box-shadow:0 4px 16px rgba(239,68,68,.4);">⏰ Time Expired</div>';
    document.body.appendChild(ov);
  });

  // Enable submit only after an answer is selected
  document.querySelectorAll('input[name="answer"]').forEach(input => {
    input.addEventListener('change', function() {
      if (locked) return;

      const newAnswer = parseInt(this.value);

      if (initialAnswer === null) {
        initialAnswer = newAnswer;
      } else if (newAnswer !== initialAnswer) {
        answerChanged = true;
      }

      userAnswer = newAnswer;

      document.querySelectorAll('.option-item').forEach(item => item.classList.remove('selected'));
      this.closest('.option-item').classList.add('selected');

      document.getElementById('submitBtn').disabled = false;
    });
  });

  document.getElementById('submitBtn').addEventListener('click', function() {
    if (locked) return;

    // Loading state
    this.disabled = true;
    this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';

    setReadOnlyState();
    sendAnswerToParent();
  });

  function setReadOnlyState() {
    document.querySelectorAll('input[name="answer"]').forEach(input => input.disabled = true);
    locked = true;
  }

  function sendAnswerToParent() {
    let changesData = null;
    if (answerChanged && initialAnswer !== null) {
      changesData = { added: [userAnswer], removed: [initialAnswer], modified_count: 1, changed: true };
    }

    if (window.parent !== window) {
      window.parent.postMessage({
        type:          'answered',
        answer:        userAnswer,
        initial_answer: answerChanged ? initialAnswer : null,
        correctAnswer: correctAnswer,
        correct:       userAnswer === correctAnswer,
        score:         userAnswer === correctAnswer ? 1 : 0,
        earned_points: userAnswer === correctAnswer ? 1 : 0,
        max_points:    1,
        changes:       changesData,
        rationale:     rationale,
        topic:    <?= json_encode($topic) ?>,
        system:   <?= json_encode($system) ?>,
        cnc:      <?= json_encode($cnc) ?>,
        dlevel:   <?= json_encode($dlevel) ?>,
        question_id:   questionId,
        question_type: 'mc'
      }, '*');
    }
  }
</script>

</body>
</html>
