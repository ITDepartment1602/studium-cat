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
        foreach ($decoded as $v) { $t = trim((string) $v); if ($t !== '') $out[] = $t; }
        return $out;
    }
    if (strpos($raw, "\n") !== false) { $parts = preg_split('/\r\n|\r|\n/', $raw); }
    else { $parts = explode(',', $raw); }
    $out = [];
    foreach ($parts as $p) { $t = trim((string) $p); if ($t !== '') $out[] = $t; }
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

$questionText   = $data['question'] ?? ($data['passage'] ?? '');
$options        = parse_list($data['options'] ?? '');
$correctAnswers = parse_list($data['correct_words'] ?? ($data['correct'] ?? ''));
$topic          = $data['topic']  ?? 'General';
$system         = $data['system'] ?? 'N/A';
$cnc            = $data['cnc']    ?? 'N/A';
$dlevel         = $data['dlevel'] ?? 'N/A';
$rationale      = $data['rationale'] ?? '';

$tabs_data = json_decode(($data['tabs'] ?? '') ?: '[]', true) ?: [];
$hasTabs   = !empty($tabs_data);

$placeholderPattern = '/_{3,}|\[\[blank\]\]|\{\{blank\}\}|\[blank_?\d*\]/i';
$placeholderCount   = preg_match_all($placeholderPattern, $questionText);
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
      --primary: #0a1628; --accent: #3b82f6; --success: #10b981; --danger: #ef4444;
      --surface: #ffffff; --border: #e2e8f0; --text: #0f172a; --text-muted: #64748b; --bg-soft: #f8fafc;
    }

    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Inter', sans-serif; background: transparent; color: var(--text); }

    .two-panel { display: flex; min-height: 100vh; overflow: hidden; }
    .left-panel { width: 40%; min-width: 260px; background: #fff; border-right: 2px solid var(--border); display: flex; flex-direction: column; flex-shrink: 0; overflow: hidden; }
    .panel-title { padding: 14px 20px; background: #f1f5f9; font-weight: 800; font-size: 11px; text-transform: uppercase; color: var(--text-muted); letter-spacing: 1px; border-bottom: 1px solid var(--border); }
    .tabs-row { display: flex; padding: 8px 12px 0; gap: 4px; border-bottom: 1px solid var(--border); overflow-x: auto; overflow-y: hidden; flex-shrink: 0; scrollbar-width: none; }
    .tabs-row::-webkit-scrollbar { height: 3px; }
    .tabs-row::-webkit-scrollbar-thumb { background: transparent; border-radius: 10px; }
    .tabs-row:hover::-webkit-scrollbar-thumb { background: #cbd5e1; }
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

    .card { max-width: 950px; margin: 0 auto; background: var(--surface); border: 1px solid var(--border); border-radius: 14px; padding: 28px; box-shadow: 0 4px 16px rgba(15,23,42,0.05); }

    .instruction-badge { display: inline-block; background: #eef2ff; color: #4338ca; font-size: 11px; font-weight: 800; letter-spacing: 0.6px; text-transform: uppercase; border-radius: 999px; padding: 6px 12px; margin-bottom: 14px; }

    .question-box { font-size: 18px; line-height: 2; color: var(--primary); margin-bottom: 20px; font-weight: 600; }

    .inline-select { display: inline-block; min-width: 170px; margin: 0 6px; padding: 8px 10px; border: 2px solid var(--border); border-radius: 10px; background: #fff; font-size: 14px; font-weight: 600; color: var(--text); outline: none; transition: all 0.2s ease; vertical-align: middle; }
    .inline-select:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(59,130,246,0.12); }
    .inline-select:disabled { opacity: .7; cursor: not-allowed; }

    .fallback-blanks { display: grid; gap: 12px; margin-top: 6px; margin-bottom: 12px; }
    .fallback-row { display: flex; align-items: stretch; background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 10px 12px; }
    .fallback-row .inline-select { margin: 0; width: 100%; min-width: 0; }

    .actions { position: sticky; bottom: 0; background: #fff; padding: 16px 0 20px; margin-top: 18px; border-top: 1px solid var(--border); display: flex; justify-content: center; z-index: 10; }
    .btn { padding: 14px 40px; border-radius: 12px; font-weight: 800; font-size: 15px; cursor: pointer; border: none; transition: all .2s ease; min-width: 220px; display: flex; align-items: center; justify-content: center; gap: 10px; }
    .btn-primary { background: linear-gradient(135deg, #3b82f6, #1d4ed8); color: #fff; box-shadow: 0 4px 14px rgba(59,130,246,.4); }
    .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(59,130,246,.5); }
    .btn-primary:disabled { opacity: .45; cursor: not-allowed; transform: none !important; box-shadow: none; }

    @media (max-width: 768px) {
      .card { padding: 14px !important; border-radius: 10px !important; max-width: 100% !important; box-shadow: none !important; }
      .question-box { font-size: 14px !important; line-height: 1.7 !important; margin-bottom: 14px !important; }
      .instruction-badge { font-size: 10px !important; padding: 5px 10px !important; }
      .inline-select { min-width: 110px !important; font-size: 12px !important; padding: 5px 8px !important; margin: 0 3px !important; }
      .fallback-row { padding: 8px 10px !important; border-radius: 10px !important; }
      .actions { padding: 10px 0 14px !important; }
      .btn { padding: 10px 16px !important; font-size: 13px !important; min-width: 0 !important; width: 100% !important; border-radius: 10px !important; }
    }
    @media (max-width: 480px) {
      .card { padding: 12px !important; }
      .question-box { font-size: 13px !important; }
      .inline-select { min-width: 90px !important; font-size: 11px !important; }
      .btn { padding: 9px 14px !important; font-size: 12px !important; }
    }

/* ── Mobile Clinical Reference ── */
@media (max-width: 768px) {
  .left-panel { display: none !important; }
  .mobile-clinref-btn {
    display: flex !important; width: 100%; align-items: center; justify-content: center;
    gap: 6px; background: #e8f4fd; color: #0369a1; border: 1.5px solid #bae6fd;
    border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 600;
    cursor: pointer; margin-bottom: 10px; text-align: center; letter-spacing: 0.1px;
    -webkit-tap-highlight-color: transparent;
  }
  .mobile-clinref-btn i { font-size: 14px; }
  .left-panel.mobile-open {
    display: flex !important; position: fixed !important; inset: 0 !important;
    z-index: 9998 !important; width: 100% !important; min-width: unset !important;
    max-width: 100% !important; height: 100dvh !important; max-height: 100% !important;
    border-right: none !important; border-bottom: none !important;
    flex-direction: column !important; overflow: hidden !important;
    border-radius: 0 !important; background: #fff !important;
  }
  .left-panel.mobile-open .panel-title {
    padding: 16px 18px !important; font-size: 13px !important; display: flex !important;
    align-items: center; justify-content: space-between; flex-shrink: 0;
    background: #f1f5f9; border-bottom: 1px solid #e2e8f0;
  }
  .mobile-clinref-close {
    display: flex !important; align-items: center; gap: 6px; background: #ef4444;
    color: #fff !important; border: none; border-radius: 10px; padding: 8px 16px;
    font-size: 14px; font-weight: 700; cursor: pointer; min-width: 80px; justify-content: center;
  }
  .left-panel.mobile-open .tabs-row {
    flex-shrink: 0; padding: 6px 8px 0 !important; gap: 3px !important;
    overflow-x: hidden; display: flex; flex-wrap: nowrap;
  }
  .left-panel.mobile-open .tab-btn {
    font-size: 11px !important; padding: 8px 10px !important; font-weight: 600;
    white-space: nowrap; flex: 1 1 0; min-width: 0; text-align: center;
    border-radius: 8px 8px 0 0; overflow: hidden; text-overflow: ellipsis;
  }
  .left-panel.mobile-open .tab-content-area {
    flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; padding: 16px !important;
  }
  .left-panel.mobile-open .clinical-record {
    font-size: 14.5px !important; line-height: 1.7 !important; padding: 12px 14px !important;
    margin-bottom: 10px !important; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0;
  }
}
@media (min-width: 769px) { .mobile-clinref-btn { display: none !important; } }
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
      <button id="submitBtn" class="btn btn-primary"><i class="fas fa-check-circle"></i> Submit Answer</button>
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
const rationale      = <?= json_encode($rationale) ?>;
const blankCount     = <?= json_encode($blankCount) ?>;
const inputs         = Array.from(document.querySelectorAll('.dd-input'));
let locked           = false;
let initialAnswers   = [];

function norm(v) { return String(v || '').trim().toLowerCase(); }

function setReadOnlyState() {
  inputs.forEach(el => el.disabled = true);
  document.getElementById('submitBtn').style.display = 'none';
  locked = true;
}

// Timeout from parent (Pressure Mode)
window.addEventListener('message', function(e) {
    if (e.data && e.data.type === 'timeout') {
        setReadOnlyState();
        const ov = document.createElement('div');
        ov.style.cssText = 'position:fixed;inset:0;background:rgba(239,68,68,.08);display:flex;align-items:flex-start;justify-content:center;padding-top:20px;z-index:9999;pointer-events:none;';
        ov.innerHTML = '<div style="background:#ef4444;color:#fff;padding:8px 22px;border-radius:100px;font-weight:800;font-size:13px;box-shadow:0 4px 16px rgba(239,68,68,.4);">⏰ Time Expired</div>';
        document.body.appendChild(ov);
    }
});

if (window.parent !== window) window.parent.postMessage({ type: 'ready' }, '*');

// Capture initial state on page load
setTimeout(function() {
    if(initialAnswers.length === 0) {
        inputs.forEach(input => initialAnswers.push(input.value || ''));
    }
}, 50);

document.getElementById('submitBtn').addEventListener('click', function() {
  if (locked) return;

  const answers = inputs.map(el => el.value);
  const hasIncomplete = answers.some(v => norm(v) === '');
  if (hasIncomplete) {
    Swal.fire({ icon: 'warning', title: 'Incomplete', text: 'Please answer all dropdown blanks before submitting.' });
    return;
  }

  if(initialAnswers.length === 0) initialAnswers = [...answers];

  const total = Math.max(correctAnswers.length, answers.length, 1);
  let earned = 0;
  for (let i = 0; i < total; i++) {
    if (norm(answers[i]) === norm(correctAnswers[i])) earned++;
  }

  const normalized = parseFloat((earned / total).toFixed(2));

  let changesData = null;
  if(JSON.stringify(initialAnswers) !== JSON.stringify(answers)){
    changesData = { modified_count: 1, changed: true };
  }

  document.getElementById('submitBtn').disabled = true;
  document.getElementById('submitBtn').innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting…';
  setReadOnlyState();

  window.parent.postMessage({
    type: 'answered',
    answer:        answers,
    initial_answer: initialAnswers.length > 0 ? initialAnswers : null,
    correctAnswer:  correctAnswers,
    correct:        earned === total,
    score:          normalized,
    max_points:     total,
    earned_points:  earned,
    changes:        changesData,
    rationale:      rationale,
    topic:   <?= json_encode($topic) ?>,
    system:  <?= json_encode($system) ?>,
    cnc:     <?= json_encode($cnc) ?>,
    dlevel:  <?= json_encode($dlevel) ?>,
    question_id:   <?= json_encode($data['id'] ?? $id) ?>,
    question_type: 'dropdown',
    source_table:  <?= json_encode($sourceTable) ?>
  }, '*');
});
</script>
<script>
(function () {
  if (window.innerWidth > 768) return;
  var lp = document.querySelector(".left-panel");
  if (!lp) return;
  var pt = lp.querySelector(".panel-title");
  if (pt) {
    var cb = document.createElement("button");
    cb.className = "mobile-clinref-close";
    cb.innerHTML = "&#10005; Close";
    cb.onclick = function () { lp.classList.remove("mobile-open"); };
    pt.appendChild(cb);
  }
  var rp = document.querySelector(".right-panel");
  var btn = document.createElement("button");
  btn.className = "mobile-clinref-btn";
  btn.innerHTML = "<i class='fas fa-folder-open'></i> View Patient Chart &amp; Lab Results";
  btn.onclick = function () { lp.classList.add("mobile-open"); };
  if (rp) rp.insertBefore(btn, rp.firstChild);
  else document.body.insertBefore(btn, document.body.firstChild);
})();
</script>
</body>
</html>
