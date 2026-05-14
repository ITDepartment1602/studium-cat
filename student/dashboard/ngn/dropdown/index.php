<?php
require_once '../../../../config.php';
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

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
$concept = $data['concept'] ?? 'General';
$narcan = $data['narcan'] ?? 'N/A';
$rationale = $data['rationale'] ?? '';
$furtherinfo = $data['furtherinfo'] ?? '';
$image = $data['image'] ?? '';
$q_uid = 'dropdown_' . ($data['id'] ?? 0);
$peer_q = mysqli_query($con, "SELECT AVG(isCorrect) * 100 as avg_score FROM exam_results WHERE question_uid = '$q_uid'");
$peer_data = mysqli_fetch_assoc($peer_q);
$avg_peer_score = ($peer_data && $peer_data['avg_score']) ? round($peer_data['avg_score'], 1) . '%' : 'N/A';

// Dynamic clinical reference tabs from `tabs` DB field (spec §1.2)
$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
$hasTabs = !empty($tabs_data);

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
    .right-panel { flex: 1; overflow-y: auto; padding: 20px; min-width: 0; }
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
    .stats-btn:hover { background: #e2e8f0; color: #0f172a; }
    .stats-btn i { font-size: 14px; color: #3b82f6; }
  </style>
</head>
<body>
<div class="two-panel">
<?php if ($hasTabs): ?>
<div class="left-panel">
  <div class="panel-title">Clinical Reference</div>
  <div class="tabs-row">
    <?php foreach ($tabs_data as $i => $tab): ?>
    <div class="tab-btn <?= $i === 0 ? 'active' : '' ?>" data-tab="drtab-<?= $i ?>"><?= htmlspecialchars($tab['title']) ?></div>
    <?php endforeach; ?>
  </div>
  <div class="tab-content-area">
    <?php foreach ($tabs_data as $i => $tab): ?>
    <div id="drtab-<?= $i ?>" class="tab-pane" <?= $i > 0 ? 'style="display:none;"' : '' ?>>
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
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:8px;">
        <div class="result-title" style="margin-bottom:0;">Performance & Rationale</div>
        <button class="stats-btn" onclick="showStatsModal()" id="questionInfoBtn" style="display:none;">
            <i class="fas fa-info-circle"></i> Question Info
        </button>
      </div>
      <div class="result-summary" id="resultSummary"></div>
      <div class="result-rationale" id="resultRationale"></div>
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

    const correctAnswers = <?= json_encode(array_values($correctAnswers)) ?>;
    const rationale = <?= json_encode($rationale) ?>;
    const furtherinfo = <?= json_encode($furtherinfo) ?>;
    const image = <?= json_encode($image) ?>;
    const blankCount = <?= json_encode($blankCount) ?>;

    /* Stats Data */
    const _qStartTime = Date.now();
    const questionStats = {
        difficulty: <?= json_encode($dlevel) ?>,
        peerScore: <?= json_encode($avg_peer_score) ?>,
        concept: <?= json_encode($concept) ?>,
        topic: <?= json_encode($topic) ?>,
        system: <?= json_encode($system) ?>,
        cnc: <?= json_encode($cnc) ?>,
        type: 'Drop-Down Cloze (Dropdown)'
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

        if (u && u === c) {
          sel.classList.add('correct-reveal');
        } else {
          sel.classList.add('wrong-reveal');
        }
      });

      document.getElementById('resultSummary').textContent = scoreText;
      const infoBtn = document.getElementById('questionInfoBtn');
      if (infoBtn) infoBtn.style.display = 'flex';
      
      let resultHtml = rationale ? rationale.replace(/\n/g, '<br>') : 'No rationale provided.';
      
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

      document.getElementById('resultRationale').innerHTML = resultHtml;
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

    // Signal parent that this iframe is ready to receive prefill data
    if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');

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
