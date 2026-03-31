<?php
require_once '../../../../config.php';
// session_start handled by config.php

function table_exists($con, $table) {
    $safe = mysqli_real_escape_string($con, $table);
    $res = mysqli_query($con, "SHOW TABLES LIKE '$safe'");
    return $res && mysqli_num_rows($res) > 0;
}

function parse_list($raw) {
    if ($raw === null) return [];
    $raw = trim((string) $raw);
    if ($raw === '') return [];

    $decoded = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
        $out = [];
        foreach ($decoded as $v) {
            $t = trim((string) $v);
            if ($t !== '') $out[] = $t;
        }
        return $out;
    }

    if (strpos($raw, "\n") !== false) {
        $parts = preg_split('/\r\n|\r|\n/', $raw);
    } else {
        $parts = explode(',', $raw);
    }

    $out = [];
    foreach ($parts as $p) {
        $t = trim((string) $p);
        if ($t !== '') $out[] = $t;
    }
    return $out;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$sourceTable = null;
$data = null;

foreach (['dropdown', 'dropdown_questions'] as $tbl) {
    if (!table_exists($con, $tbl)) continue;

    if ($id > 0) {
        $q = mysqli_query($con, "SELECT * FROM `$tbl` WHERE id='$id' LIMIT 1");
    } else {
        $q = mysqli_query($con, "SELECT * FROM `$tbl` ORDER BY RAND() LIMIT 1");
    }

    if ($q && mysqli_num_rows($q) > 0) {
        $data = mysqli_fetch_assoc($q);
        $sourceTable = $tbl;
        break;
    }
}

if (!$data) {
    die('<div style="font-family: Inter, sans-serif; padding: 24px;">No dropdown question found.</div>');
}

$questionText = $data['question'] ?? ($data['passage'] ?? '');
$options = parse_list($data['options'] ?? '');
$correctAnswers = parse_list($data['correct_words'] ?? ($data['correct'] ?? ''));
$topic = $data['topic'] ?? 'General';
$system = $data['system'] ?? 'N/A';
$cnc = $data['cnc'] ?? 'N/A';
$dlevel = $data['dlevel'] ?? 'N/A';
$rationale = $data['rationale'] ?? '';

$placeholderPattern = '/_{3,}|\[\[blank\]\]|\{\{blank\}\}/i';
$placeholderCount = preg_match_all($placeholderPattern, $questionText);
$blankCount = max($placeholderCount, count($correctAnswers));
if ($blankCount < 1) $blankCount = 1;

if (empty($options)) {
    $options = array_values(array_unique($correctAnswers));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dropdown Question</title>
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
      padding: 20px;
    }

    .card {
      max-width: 950px;
      margin: 0 auto;
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: 14px;
      padding: 28px;
      box-shadow: 0 4px 16px rgba(15, 23, 42, 0.05);
    }

    .previous-badge {
      display: none;
      background: #f1f5f9;
      color: #475569;
      font-size: 12px;
      font-weight: 600;
      padding: 8px 14px;
      border-radius: 8px;
      margin-bottom: 16px;
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
      padding: 6px 12px;
      margin-bottom: 14px;
    }

    .question-box {
      font-size: 18px;
      line-height: 2;
      color: var(--primary);
      margin-bottom: 20px;
      font-weight: 600;
    }

    .inline-select {
      display: inline-block;
      min-width: 170px;
      margin: 0 6px;
      padding: 8px 10px;
      border: 2px solid var(--border);
      border-radius: 10px;
      background: #fff;
      font-size: 14px;
      font-weight: 600;
      color: var(--text);
      outline: none;
      transition: all 0.2s ease;
      vertical-align: middle;
    }

    .inline-select:focus {
      border-color: var(--accent);
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
    }

    .inline-select.correct-reveal {
      border-color: var(--success);
      background: #ecfdf5;
      color: #166534;
    }

    .inline-select.wrong-reveal {
      border-color: var(--danger);
      background: #fef2f2;
      color: #991b1b;
    }
    
    .inline-select.omitted-reveal {
      border-color: #f59e0b;
      background: #fffbeb;
      color: #92400e;
      text-decoration: line-through;
      opacity: 0.75;
    }

    .fallback-blanks {
      display: grid;
      gap: 12px;
      margin-top: 6px;
      margin-bottom: 12px;
    }

    .fallback-row {
      display: flex;
      align-items: stretch;
      background: #f8fafc;
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 10px 12px;
    }

    .fallback-row .inline-select {
      margin: 0;
      width: 100%;
      min-width: 0;
    }

    .actions {
      margin-top: 18px;
      display: flex;
      gap: 10px;
    }

    .btn {
      border: none;
      border-radius: 10px;
      padding: 11px 24px;
      font-size: 14px;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .btn-primary {
      background: var(--primary);
      color: #fff;
    }

    .btn-primary:hover {
      transform: translateY(-1px);
      background: #1e293b;
    }

    #result {
      display: none;
      margin-top: 20px;
      border-left: 4px solid var(--accent);
      background: var(--bg-soft);
      border-radius: 10px;
      padding: 18px;
    }

    .result-title {
      font-size: 12px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-muted);
      margin-bottom: 8px;
    }

    .result-summary {
      font-size: 15px;
      font-weight: 700;
      margin-bottom: 8px;
    }

    .result-rationale {
      font-size: 14px;
      line-height: 1.6;
    }

    @media (max-width: 640px) {
      body { padding: 10px; }
      .card { padding: 16px; border-radius: 10px; }
      .question-box { font-size: 16px; line-height: 1.8; }
      .inline-select { min-width: 130px; font-size: 13px; }
      .btn { width: 100%; }
    }
  </style>
</head>
<body>
  <div class="card">
    <div class="previous-badge" id="prevBadge">
      <i class="fas fa-lock"></i> This question has been submitted and is now read-only.
    </div>

    <div class="instruction-badge">Drop-Down Cloze</div>

    <?php if ($placeholderCount > 0): ?>
      <?php
        $slot = 0;
        $rendered = preg_replace_callback($placeholderPattern, function() use (&$slot, $blankCount, $options) {
            if ($slot >= $blankCount) return '';
            $name = 'blank_' . $slot;
            $html = '<select class="inline-select dd-input" data-idx="' . $slot . '" name="' . $name . '">';
            $html .= '<option value="">Select answer</option>';
            foreach ($options as $opt) {
                $safe = htmlspecialchars($opt, ENT_QUOTES, 'UTF-8');
                $html .= '<option value="' . $safe . '">' . $safe . '</option>';
            }
            $html .= '</select>';
            $slot++;
            return $html;
        }, htmlspecialchars($questionText, ENT_QUOTES, 'UTF-8'));
      ?>
      <div class="question-box"><?= $rendered ?></div>
    <?php else: ?>
      <div class="question-box"><?= nl2br(htmlspecialchars($questionText, ENT_QUOTES, 'UTF-8')) ?></div>
      <div class="fallback-blanks">
        <?php for ($i = 0; $i < $blankCount; $i++): ?>
          <div class="fallback-row">
            <select class="inline-select dd-input" data-idx="<?= $i ?>" name="blank_<?= $i ?>">
              <option value="">Select answer</option>
              <?php foreach ($options as $opt): ?>
                <option value="<?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($opt, ENT_QUOTES, 'UTF-8') ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endfor; ?>
      </div>
    <?php endif; ?>

    <div class="actions">
      <button id="submitBtn" class="btn btn-primary">Submit Answer</button>
    </div>

    <div id="result">
      <div class="result-title">Performance & Rationale</div>
      <div class="result-summary" id="resultSummary"></div>
      <div class="result-rationale" id="resultRationale"></div>
    </div>
  </div>

  <script>
    const correctAnswers = <?= json_encode(array_values($correctAnswers)) ?>;
    const rationale = <?= json_encode($rationale) ?>;
    const blankCount = <?= json_encode($blankCount) ?>;
    const inputs = Array.from(document.querySelectorAll('.dd-input'));
    let locked = false;
    let isReviewMode = false;
    let initialAnswers = [];
    let hasInteracted = false;
    
    // Capture initial state on page load (for fresh exams)
    function captureInitialState() {
        if(initialAnswers.length === 0) {
            inputs.forEach(input => {
                initialAnswers.push(input.value || '');
            });
        }
    }
    setTimeout(captureInitialState, 50);

    function norm(v) {
      return String(v || '').trim().toLowerCase();
    }

    function setReadOnlyState() {
      inputs.forEach(el => el.disabled = true);
      document.getElementById('submitBtn').style.display = 'none';
      locked = true;
    }

    function showResult(scoreText, userAnswers, prevInitial = []) {
      inputs.forEach((sel, idx) => {
        sel.classList.remove('correct-reveal', 'wrong-reveal', 'omitted-reveal');
        const u = norm(userAnswers[idx] || '');
        const c = norm(correctAnswers[idx] || '');
        const displayInitial = prevInitial.length > 0 ? prevInitial : initialAnswers;
        const initial = norm(displayInitial[idx] || '');
        
        // Show omitted if was filled but now different
        if(initial && initial !== u && u !== ''){
          sel.classList.add('omitted-reveal');
        } else if (u && u === c) {
          sel.classList.add('correct-reveal');
        } else {
          sel.classList.add('wrong-reveal');
        }
      });

      document.getElementById('resultSummary').textContent = scoreText;
      document.getElementById('resultRationale').textContent = rationale || 'No rationale provided.';
      $('#result').fadeIn();
      setReadOnlyState();
    }

    function applyPrevious(answerArray, showRationale, score, earned, maxPoints, prevInitial = []) {
      if (!Array.isArray(answerArray)) {
        if (answerArray === null || typeof answerArray === 'undefined' || answerArray === '') return;
        answerArray = [String(answerArray)];
      }
      if (answerArray.length === 0) return;

      document.getElementById('prevBadge').style.display = 'block';
      initialAnswers = prevInitial.length > 0 ? prevInitial : answerArray;
      inputs.forEach((el, idx) => {
        const val = answerArray[idx] ?? '';
        if (val !== '') el.value = val;
      });

      if (showRationale) {
        const s = typeof score !== 'undefined' ? Number(score) : 0;
        const e = typeof earned !== 'undefined' ? Number(earned) : 0;
        const m = typeof maxPoints !== 'undefined' ? Number(maxPoints) : (correctAnswers.length || blankCount || 1);
        showResult(`Score: ${Math.round(s * 100)}% (${e}/${m} pts)`, answerArray, prevInitial);
      }
    }

    window.addEventListener('message', (event) => {
      if (!event.data || (event.data.type !== 'prefill' && event.data.type !== 'previous')) return;
      isReviewMode = event.data.isReview ?? false;
      applyPrevious(
        event.data.answer || [],
        !!event.data.showRationale,
        event.data.score,
        event.data.earned_points,
        event.data.max_points,
        event.data.initial_answer || []
      );
    });

    document.getElementById('submitBtn').addEventListener('click', () => {
      if (locked || isReviewMode) return;

      const answers = inputs.map(el => el.value);
      const hasIncomplete = answers.some(v => norm(v) === '');
      if (hasIncomplete) {
        Swal.fire({
          icon: 'warning',
          title: 'Incomplete',
          text: 'Please answer all dropdown blanks before submitting.'
        });
        return;
      }
      
      // Capture initial if not done yet (safety net)
      if(initialAnswers.length === 0){
        initialAnswers = [...answers];
      }

      const total = Math.max(correctAnswers.length, answers.length, 1);
      let earned = 0;
      for (let i = 0; i < total; i++) {
        if (norm(answers[i]) === norm(correctAnswers[i])) earned++;
      }

      const normalized = parseFloat((earned / total).toFixed(2));
      showResult(`Score: ${Math.round(normalized * 100)}% (${earned}/${total} pts)`, answers);
      
      // Calculate changes
      let changesData = null;
      if(JSON.stringify(initialAnswers) !== JSON.stringify(answers)){
        changesData = {
          modified_count: 1,
          changed: true
        };
      }

      window.parent.postMessage({
        type: 'answered',
        answer: answers,
        initial_answer: initialAnswers.length > 0 ? initialAnswers : null,
        correctAnswer: correctAnswers,
        correct: earned === total,
        score: normalized,
        max_points: total,
        earned_points: earned,
        changes: changesData,
        rationale: rationale,
        topic: <?= json_encode($topic) ?>,
        system: <?= json_encode($system) ?>,
        cnc: <?= json_encode($cnc) ?>,
        dlevel: <?= json_encode($dlevel) ?>,
        question_id: <?= json_encode($data['id'] ?? $id) ?>,
        question_type: 'dropdown',
        source_table: <?= json_encode($sourceTable) ?>
      }, '*');
    });
  </script>
</body>
</html>
