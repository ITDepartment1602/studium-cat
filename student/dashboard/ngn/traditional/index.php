<?php
require_once '../../../../config.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

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
$concept = $data['concept'] ?? 'General';
$narcan = $data['narcan'] ?? 'N/A';
$furtherinfo = $data['furtherinfo'] ?? '';
$image = $data['image'] ?? '';

// Dynamic clinical reference tabs from `tabs` DB field (spec §1.2)
$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
$hasTabs = !empty($tabs_data);

// Fetch Stats
$q_uid = 'traditional_' . $data['id'];
$peer_q = mysqli_query($con, "SELECT AVG(isCorrect) * 100 as avg_score FROM exam_results WHERE question_uid = '$q_uid'");
$peer_data = mysqli_fetch_assoc($peer_q);
$avg_peer_score = $peer_data['avg_score'] ? round($peer_data['avg_score'], 1) . '%' : 'N/A';

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
    .tabs-row {
      display: flex;
      padding: 8px 12px 0;
      gap: 4px;
      border-bottom: 1px solid var(--border);
      overflow-x: auto;
      overflow-y: hidden;
      flex-shrink: 0;
      scrollbar-width: none;
    }
    .tabs-row::-webkit-scrollbar {
      height: 3px;
    }
    .tabs-row::-webkit-scrollbar-track {
      background: transparent;
    }
    .tabs-row::-webkit-scrollbar-thumb {
      background: transparent;
      border-radius: 10px;
    }
    .tabs-row:hover::-webkit-scrollbar-thumb {
      background: #cbd5e1;
    }
    .tabs-row:hover {
      scrollbar-width: thin;
    }
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

    .nclex-tips {
        margin-top: 24px;
        padding: 20px;
        background: #f0fdf4;
        border-radius: 12px;
        border: 1px solid #bbf7d0;
    }
    .tips-title {
        font-weight: 800;
        color: #166534;
        font-size: 13px;
        text-transform: uppercase;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        letter-spacing: 0.5px;
    }
    .tips-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .tips-list li {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        font-size: 14.5px;
        color: #15803d;
        margin-bottom: 10px;
        line-height: 1.5;
    }
    .tips-list li i {
        color: #22c55e;
        margin-top: 3px;
        flex-shrink: 0;
        font-size: 16px;
    }
    .rationale-image-wrapper {
        margin-top: 24px;
        padding-top: 24px;
        border-top: 1px solid var(--border);
    }
    .image-title {
        font-weight: 800;
        color: var(--text-muted);
        font-size: 11px;
        text-transform: uppercase;
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .tip-highlight {
        background: #fef08a;
        padding: 2px 4px;
        border-radius: 4px;
        font-weight: 700;
        color: #854d0e;
        border-bottom: 1.5px solid #f59e0b;
        display: inline;
        line-height: 1;
        white-space: normal;
    }
    .stats-btn {
        background: #f1f5f9;
        color: #64748b;
        border: 1px solid #e2e8f0;
        padding: 6px 10px;
        border-radius: 8px;
        font-size: 11px;
        font-weight: 700;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .stats-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .stats-btn i { font-size: 14px; color: #3b82f6; }

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
    const rationale = <?= json_encode($rationale) ?>;
    const furtherinfo = <?= json_encode($furtherinfo) ?>;

    /* Stats Data */
    const _qStartTime = Date.now();
    const questionStats = {
        difficulty: <?= json_encode($dlevel) ?>,
        peerScore: <?= json_encode($avg_peer_score) ?>,
        concept: <?= json_encode($concept) ?>,
        topic: <?= json_encode($topic) ?>,
        system: <?= json_encode($system) ?>,
        cnc: <?= json_encode($cnc) ?>,
        type: 'Traditional Multiple Choice'
    };

    window.showStatsModal = function() {
        const secs = Math.round((Date.now() - _qStartTime) / 1000);
        const timeTaken = secs < 60 ? secs + ' s' : Math.floor(secs/60) + ' m ' + (secs%60) + ' s';
        Swal.fire({
            title: '<i class="fas fa-chart-bar" style="color:#3b82f6; margin-right:6px;"></i> Statistics',
            html: `
                <div style="text-align:left; padding:4px 0;">
                    <div style="display:flex; gap:10px; margin-bottom:16px;">
                        <div style="flex:1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center;">
                            <div style="font-size:18px; margin-bottom:4px;"><i class="fas fa-gauge-high" style="color:#f59e0b;"></i></div>
                            <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Difficulty level</div>
                            <div style="font-size:13px; font-weight:800; color:#0f172a;">${questionStats.difficulty}</div>
                        </div>
                        <div style="flex:1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center;">
                            <div style="font-size:18px; margin-bottom:4px;"><i class="fas fa-users" style="color:#8b5cf6;"></i></div>
                            <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Avg. Peers Score</div>
                            <div style="font-size:13px; font-weight:800; color:#10b981;">${questionStats.peerScore}</div>
                        </div>
                        <div style="flex:1; background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:12px; text-align:center;">
                            <div style="font-size:18px; margin-bottom:4px;"><i class="fas fa-hourglass-half" style="color:#3b82f6;"></i></div>
                            <div style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:4px;">Time taken</div>
                            <div style="font-size:13px; font-weight:800; color:#0f172a;">${timeTaken}</div>
                        </div>
                    </div>
                    <div style="border-top:1px solid #e2e8f0; padding-top:14px; display:flex; flex-direction:column; gap:10px;">
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; min-width:130px;">Subject</span>
                            <span style="background:#eff6ff; color:#3b82f6; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.concept}</span>
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px;">Lesson</span>
                            <span style="background:#eff6ff; color:#3b82f6; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.topic}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; min-width:130px;">Client Need Area</span>
                            <span style="background:#f0fdf4; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.cnc}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; min-width:130px;">Client Need Topic</span>
                            <span style="background:#f0fdf4; color:#16a34a; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.system}</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                            <span style="font-size:10px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; min-width:130px;">Question Type</span>
                            <span style="background:#fef3c7; color:#d97706; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;">${questionStats.type}</span>
                        </div>
                    </div>
                </div>
            `,
            confirmButtonText: 'Got it',
            confirmButtonColor: '#3b82f6',
            width: '500px'
        });
    };
  const image = <?= json_encode($image) ?>;
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

  // Signal parent that this iframe is ready to receive prefill data
  if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');

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

      if (value === correctAnswer) {
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

    resultTitle.className = 'result-title ' + (isCorrect ? 'correct' : 'incorrect');
    resultTitle.innerHTML = `
        <div style="display:flex; align-items:center; justify-content: space-between; width: 100%;">
            <div style="display:flex; align-items:center; gap:8px;">
                <i class="fas ${isCorrect ? 'fa-check-circle' : 'fa-times-circle'}"></i>
                <span>${isCorrect ? 'Correct' : 'Incorrect'}</span>
            </div>
            <button class="stats-btn" onclick="showStatsModal()">
                <i class="fas fa-info-circle"></i> Question Info
            </button>
        </div>
    `;

    // Add omitted answer note if applicable
    let rationaleText = rationale ? rationale.replace(/\n/g, '<br>') : 'No rationale provided.';
    
    let resultHtml = rationaleText;

    if (furtherinfo) {
        let tips = [];
        try {
            let decoded = JSON.parse(furtherinfo);
            if (Array.isArray(decoded)) tips = decoded;
            else tips = [furtherinfo];
        } catch (e) {
            tips = furtherinfo.split('\n').filter(l => l.trim() !== '');
        }

        resultHtml += '<div class="nclex-tips">';
        resultHtml += '<div class="tips-title"><i class="fas fa-lightbulb"></i> NCLEX Tips & Further Information</div>';
        resultHtml += '<ul class="tips-list">';
        tips.forEach(t => {
            // Collapse newlines and extra spaces for a continuous sentence
            let cleanTip = t.replace(/\s+/g, ' ').trim();
            // Highlight words wrapped in %
            let highlighted = cleanTip.replace(/%([^%]+)%/g, '<span class="tip-highlight">$1</span>');
            resultHtml += '<li><i class="fas fa-check-circle"></i> <span>' + highlighted + '</span></li>';
        });
        resultHtml += '</ul></div>';
    }
    
    if (image) {
        resultHtml += '<div class="rationale-image-wrapper">';
        resultHtml += '<div class="image-title"><i class="fas fa-image"></i> Related Illustration</div>';
        resultHtml += '<img src="../../../../admin/dashboard/pages/uploads/' + image + '" alt="NCLEX Illustration" style="max-width:100%; border-radius:12px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">';
        resultHtml += '</div>';
    }

    rationaleContent.innerHTML = resultHtml;

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
