<?php
include '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$user_id = $_SESSION['user_id'];

$select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'");
$fetch  = mysqli_fetch_assoc($select);
date_default_timezone_set('Asia/Manila');
$daysLeft = floor((strtotime($fetch['dateexpired']) - time()) / 86400);
if ($daysLeft < 0) { header('Location: ../../logout.php'); exit; }

$quizCon = getQuizConnection();

// ── Traditional exams ──
$tradResult = mysqli_query($quizCon,
    "SELECT examTaken,
            MAX(CASE WHEN questionNumber = 150 THEN totalTime ELSE NULL END) AS totalTime,
            MAX(CASE WHEN questionNumber = 150 THEN timestamp ELSE NULL END) AS timestamp,
            topics1,
            SUM(CASE WHEN isCorrect = 1 THEN 1 ELSE 0 END) AS correctAnswers,
            COUNT(*) AS totalQuestions
     FROM review
     WHERE studentId = '$user_id'
     GROUP BY examTaken, topics1
     HAVING COUNT(*) >= 145
     ORDER BY examTaken DESC"
);
$tradExams = [];
$examCounter = 1;
while ($row = mysqli_fetch_assoc($tradResult)) {
    $tradExams[] = $row;
}
// Label newest-first, then reverse so newest is index 0
$tradTotal_count = count($tradExams);
foreach ($tradExams as $i => &$e) {
    $e['exam_label'] = 'Exam ' . ($tradTotal_count - $i);
}
unset($e);

// ── NGN exams ──
$ngnRows = db()->fetchAll(
    "SELECT examTaken,
            COUNT(*) AS totalQuestions,
            SUM(isCorrect) AS correctAnswers,
            MAX(timestamp) AS timestamp,
            MAX(totalTime) AS totalTime,
            GROUP_CONCAT(DISTINCT concept ORDER BY concept SEPARATOR ', ') AS concepts
     FROM exam_results
     WHERE student_id = ?
     GROUP BY examTaken
     ORDER BY examTaken DESC",
    [$user_id]
);
$ngnExams = [];
foreach ($ngnRows as $row) {
    $ngnExams[] = $row;
}
$ngnTotal_count = count($ngnExams);
foreach ($ngnExams as $i => &$e) {
    $e['exam_label'] = 'Exam ' . ($ngnTotal_count - $i);
}
unset($e);

// ── Summary stats ──
$tradTotal   = count($tradExams);
$ngnTotal    = count($ngnExams);
$overallTotal = $tradTotal + $ngnTotal;

$tradTime = array_sum(array_column($tradExams, 'totalTime'));
$ngnTime  = array_sum(array_column($ngnExams,  'totalTime'));
$overallTime = $tradTime + $ngnTime;

// Best topic (traditional)
$topicCorrects = [];
foreach ($tradExams as $e) {
    $t = $e['topics1'] ?? 'Unknown';
    $topicCorrects[$t] = ($topicCorrects[$t] ?? 0) + $e['correctAnswers'];
}
$bestTopic = !empty($topicCorrects) ? array_keys($topicCorrects, max($topicCorrects))[0] : 'N/A';

// Best concept (NGN)
$conceptCorrects = [];
foreach ($ngnExams as $e) {
    foreach (explode(', ', $e['concepts'] ?? '') as $c) {
        $c = trim($c);
        if ($c) $conceptCorrects[$c] = ($conceptCorrects[$c] ?? 0) + $e['correctAnswers'];
    }
}
$bestConcept = !empty($conceptCorrects) ? array_keys($conceptCorrects, max($conceptCorrects))[0] : 'N/A';

function formatTime($secs) {
    $secs = (int)$secs;
    $h = floor($secs / 3600); $m = floor(($secs % 3600) / 60); $s = $secs % 60;
    $out = '';
    if ($h > 0) $out .= $h . 'h ';
    if ($m > 0) $out .= $m . 'm ';
    $out .= $s . 's';
    return trim($out) ?: '0s';
}

$pageTitle = 'Exam History — Studium';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_layout/head.php'; ?>
  <style>
  /* ── Filter tabs ── */
  .s-hist-tabs { display:flex; gap:6px; }
  .s-hist-tab {
    display:inline-flex; align-items:center; gap:6px;
    padding:7px 16px; border-radius:8px; border:1.5px solid var(--s-border);
    background:transparent; font-size:0.8rem; font-weight:600;
    color:var(--s-muted); cursor:pointer; transition:all 0.2s;
  }
  .s-hist-tab.active {
    background:var(--s-primary); border-color:var(--s-primary); color:#fff;
  }
  .s-hist-tab:hover:not(.active) { border-color:var(--s-primary); color:var(--s-primary); }

  /* ── KPI cards ── */
  .s-kpi-card { background:var(--s-card-bg); border:1.5px solid var(--s-border); border-radius:14px; padding:20px; display:flex; flex-direction:column; gap:4px; }
  .s-kpi-icon { width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin-bottom:8px; }
  .s-kpi-icon i { font-size:1.2rem; }
  .s-kpi-num { font-size:1.8rem; font-weight:800; color:var(--s-text); line-height:1.1; }
  .s-kpi-label { font-size:0.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.5px; color:var(--s-muted); }
  .s-kpi-sub { font-size:0.75rem; color:var(--s-muted); margin-top:2px; }

  /* ── Modern table ── */
  .s-hist-table-wrap { background:var(--s-card-bg); border:1.5px solid var(--s-border); border-radius:14px; overflow:hidden; }
  .s-hist-table-header { padding:16px 20px; border-bottom:1.5px solid var(--s-border); display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:10px; }
  .s-hist-table { width:100%; border-collapse:collapse; font-size:0.83rem; }
  .s-hist-table thead tr { background:var(--s-bg, #f8fafc); }
  .s-hist-table thead th {
    padding:11px 16px; text-align:left; font-size:0.72rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.5px; color:var(--s-muted);
    border-bottom:1.5px solid var(--s-border); white-space:nowrap;
  }
  .s-hist-table tbody tr { border-bottom:1px solid var(--s-border); transition:background 0.15s; }
  .s-hist-table tbody tr:last-child { border-bottom:none; }
  .s-hist-table tbody tr:hover { background:rgba(0,124,191,0.04); }
  .s-hist-table td { padding:13px 16px; vertical-align:middle; color:var(--s-text); }
  .s-hist-exam-no { font-weight:700; color:var(--s-primary); }
  .s-hist-score-pass { background:rgba(13,148,136,.12); color:#0D9488; font-weight:700; padding:3px 10px; border-radius:20px; font-size:0.78rem; white-space:nowrap; }
  .s-hist-score-warn { background:rgba(217,119,6,.12); color:#d97706; font-weight:700; padding:3px 10px; border-radius:20px; font-size:0.78rem; white-space:nowrap; }
  .s-hist-score-fail { background:rgba(239,68,68,.12); color:#ef4444; font-weight:700; padding:3px 10px; border-radius:20px; font-size:0.78rem; white-space:nowrap; }
  .s-hist-type-badge { padding:2px 10px; border-radius:20px; font-size:0.7rem; font-weight:600; }
  .s-hist-type-trad { background:rgba(0,124,191,.1); color:var(--s-primary); }
  .s-hist-type-ngn  { background:rgba(13,148,136,.1); color:#0D9488; }

  /* ── Pagination ── */
  .s-hist-pagination { display:flex; align-items:center; justify-content:space-between; padding:14px 20px; border-top:1.5px solid var(--s-border); flex-wrap:wrap; gap:10px; }
  .s-hist-page-info { font-size:0.78rem; color:var(--s-muted); }
  .s-hist-page-btns { display:flex; gap:4px; }
  .s-page-btn {
    min-width:32px; height:32px; border-radius:7px; border:1.5px solid var(--s-border);
    background:transparent; font-size:0.8rem; font-weight:600; color:var(--s-muted);
    cursor:pointer; transition:all 0.2s; display:flex; align-items:center; justify-content:center; padding:0 8px;
  }
  .s-page-btn.active { background:var(--s-primary); border-color:var(--s-primary); color:#fff; }
  .s-page-btn:hover:not(.active):not(:disabled) { border-color:var(--s-primary); color:var(--s-primary); }
  .s-page-btn:disabled { opacity:.4; cursor:not-allowed; }

  /* ── Empty state ── */
  .s-hist-empty { padding:48px 20px; text-align:center; color:var(--s-muted); }
  .s-hist-empty i { font-size:2.5rem; margin-bottom:12px; display:block; opacity:.4; }
  </style>
</head>
<body>

<?php include '_layout/sidebar.php'; ?>

<main class="s-main">
  <div class="s-page-header">
    <h1>Exam History</h1>
    <p>Review your past exam performance and track your progress</p>
  </div>

  <!-- ── Filter Tabs ── -->
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div class="s-hist-tabs">
      <button class="s-hist-tab active" onclick="setHistFilter('overall')" id="tab-overall">
        <i class="bi bi-grid-3x3-gap-fill"></i> Overall
      </button>
      <button class="s-hist-tab" onclick="setHistFilter('trad')" id="tab-trad">
        <i class="bi bi-journals"></i> Traditional
      </button>
      <button class="s-hist-tab" onclick="setHistFilter('ngn')" id="tab-ngn">
        <i class="bi bi-lightbulb-fill"></i> NGN
      </button>
    </div>
  </div>

  <!-- ── KPI Cards ── -->
  <div class="row g-3 mb-4" id="kpi-overall">
    <div class="col-md-4 col-12">
      <div class="s-kpi-card">
        <div class="s-kpi-icon" style="background:rgba(13,148,136,.1);"><i class="bi bi-journal-check" style="color:#0D9488;"></i></div>
        <div class="s-kpi-num"><?= $overallTotal ?></div>
        <div class="s-kpi-label">Total Exams Taken</div>
        <div class="s-kpi-sub"><?= $tradTotal ?> Traditional &nbsp;·&nbsp; <?= $ngnTotal ?> NGN</div>
      </div>
    </div>
    <div class="col-md-4 col-12">
      <div class="s-kpi-card">
        <div class="s-kpi-icon" style="background:rgba(0,124,191,.1);"><i class="bi bi-clock-history" style="color:var(--s-primary);"></i></div>
        <div class="s-kpi-num" style="font-size:1.4rem;"><?= formatTime($overallTime) ?></div>
        <div class="s-kpi-label">Total Time Spent</div>
        <div class="s-kpi-sub">Cumulative practice time</div>
      </div>
    </div>
    <div class="col-md-4 col-12">
      <div class="s-kpi-card">
        <div class="s-kpi-icon" style="background:rgba(245,158,11,.1);"><i class="bi bi-trophy-fill" style="color:#f59e0b;"></i></div>
        <div class="s-kpi-num" style="font-size:1rem; padding-top:6px;"><?= htmlspecialchars($bestTopic) ?></div>
        <div class="s-kpi-label">Best Topic</div>
        <div class="s-kpi-sub">Highest correct answers</div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4" id="kpi-trad" style="display:none;">
    <div class="col-md-4 col-12">
      <div class="s-kpi-card">
        <div class="s-kpi-icon" style="background:rgba(0,124,191,.1);"><i class="bi bi-journals" style="color:var(--s-primary);"></i></div>
        <div class="s-kpi-num"><?= $tradTotal ?></div>
        <div class="s-kpi-label">Traditional Exams</div>
        <div class="s-kpi-sub"><?= $tradTotal === 1 ? '1 exam completed' : $tradTotal . ' exams completed' ?></div>
      </div>
    </div>
    <div class="col-md-4 col-12">
      <div class="s-kpi-card">
        <div class="s-kpi-icon" style="background:rgba(0,124,191,.1);"><i class="bi bi-clock-history" style="color:var(--s-primary);"></i></div>
        <div class="s-kpi-num" style="font-size:1.4rem;"><?= formatTime($tradTime) ?></div>
        <div class="s-kpi-label">Time Spent</div>
        <div class="s-kpi-sub">Traditional practice time</div>
      </div>
    </div>
    <div class="col-md-4 col-12">
      <div class="s-kpi-card">
        <div class="s-kpi-icon" style="background:rgba(245,158,11,.1);"><i class="bi bi-trophy-fill" style="color:#f59e0b;"></i></div>
        <div class="s-kpi-num" style="font-size:1rem; padding-top:6px;"><?= htmlspecialchars($bestTopic) ?></div>
        <div class="s-kpi-label">Best Topic</div>
        <div class="s-kpi-sub">Highest correct answers</div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4" id="kpi-ngn" style="display:none;">
    <div class="col-md-4 col-12">
      <div class="s-kpi-card">
        <div class="s-kpi-icon" style="background:rgba(13,148,136,.1);"><i class="bi bi-lightbulb-fill" style="color:#0D9488;"></i></div>
        <div class="s-kpi-num"><?= $ngnTotal ?></div>
        <div class="s-kpi-label">NGN Exams</div>
        <div class="s-kpi-sub"><?= $ngnTotal === 1 ? '1 exam completed' : $ngnTotal . ' exams completed' ?></div>
      </div>
    </div>
    <div class="col-md-4 col-12">
      <div class="s-kpi-card">
        <div class="s-kpi-icon" style="background:rgba(13,148,136,.1);"><i class="bi bi-clock-history" style="color:#0D9488;"></i></div>
        <div class="s-kpi-num" style="font-size:1.4rem;"><?= formatTime($ngnTime) ?></div>
        <div class="s-kpi-label">Time Spent</div>
        <div class="s-kpi-sub">NGN practice time</div>
      </div>
    </div>
    <div class="col-md-4 col-12">
      <div class="s-kpi-card">
        <div class="s-kpi-icon" style="background:rgba(13,148,136,.1);"><i class="bi bi-patch-check-fill" style="color:#0D9488;"></i></div>
        <div class="s-kpi-num" style="font-size:1rem; padding-top:6px;"><?= htmlspecialchars($bestConcept) ?></div>
        <div class="s-kpi-label">Best Concept</div>
        <div class="s-kpi-sub">Highest correct answers</div>
      </div>
    </div>
  </div>

  <!-- ── Table ── -->
  <div class="s-hist-table-wrap">
    <div class="s-hist-table-header">
      <span style="font-weight:700; font-size:0.9rem; color:var(--s-text);"><i class="bi bi-table me-2" style="color:var(--s-primary);"></i><span id="table-heading">All Exam Records</span></span>
      <input type="text" id="histSearch" class="s-input" placeholder="&#xF52A; Search..." style="max-width:200px; font-size:0.8rem; padding:6px 12px;" oninput="renderTable()">
    </div>
    <div style="overflow-x:auto;">
      <table class="s-hist-table">
        <thead>
          <tr>
            <th>Exam No.</th>
            <th>Type</th>
            <th>Date &amp; Time</th>
            <th>Topic / Concept</th>
            <th>Score</th>
            <th>Time Taken</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody id="histTableBody"></tbody>
      </table>
    </div>
    <div id="histEmpty" class="s-hist-empty" style="display:none;">
      <i class="bi bi-journal-x"></i>
      <div style="font-weight:600; margin-bottom:4px;">No exam records found</div>
      <div style="font-size:0.8rem;">Complete an exam to see it here.</div>
    </div>
    <div class="s-hist-pagination">
      <div class="s-hist-page-info" id="histPageInfo"></div>
      <div class="s-hist-page-btns" id="histPageBtns"></div>
    </div>
  </div>

</main>

<script>
const PAGE_SIZE = 5;
let currentFilter = 'overall';
let currentPage   = 1;

const tradData = <?= json_encode(array_map(function($e) use ($user_id) {
  return [
    'exam_label'     => $e['exam_label'],
    'examTaken'      => $e['examTaken'],
    'type'           => 'Traditional',
    'timestamp'      => $e['timestamp'],
    'topic'          => $e['topics1'] ?? '—',
    'correctAnswers' => (int)$e['correctAnswers'],
    'totalQuestions' => (int)$e['totalQuestions'],
    'totalTime'      => (int)$e['totalTime'],
    'link'           => 'history_details.php?examTaken=' . $e['examTaken'] . '&userId=' . $user_id,
  ];
}, $tradExams), JSON_UNESCAPED_UNICODE) ?>;

const ngnData = <?= json_encode(array_map(function($e) use ($user_id) {
  return [
    'exam_label'     => $e['exam_label'],
    'examTaken'      => $e['examTaken'],
    'type'           => 'NGN',
    'timestamp'      => $e['timestamp'],
    'topic'          => $e['concepts'] ?? '—',
    'correctAnswers' => (int)$e['correctAnswers'],
    'totalQuestions' => (int)$e['totalQuestions'],
    'totalTime'      => (int)$e['totalTime'],
    'link'           => 'ngn/history_details.php?examTaken=' . $e['examTaken'] . '&userId=' . $user_id,
  ];
}, $ngnExams), JSON_UNESCAPED_UNICODE) ?>;

function formatTime(s) {
  s = parseInt(s) || 0;
  const h = Math.floor(s / 3600), m = Math.floor((s % 3600) / 60), sec = s % 60;
  let out = '';
  if (h) out += h + 'h ';
  if (m) out += m + 'm ';
  out += sec + 's';
  return out.trim() || '0s';
}

function getFilteredData() {
  const q = document.getElementById('histSearch').value.toLowerCase();
  let data = currentFilter === 'trad' ? tradData : currentFilter === 'ngn' ? ngnData : [...tradData, ...ngnData];
  if (q) data = data.filter(r =>
    r.exam_label.toLowerCase().includes(q) ||
    r.topic.toLowerCase().includes(q) ||
    r.type.toLowerCase().includes(q)
  );
  return data;
}

function renderTable() {
  currentPage = 1;
  renderPage();
}

function renderPage() {
  const data  = getFilteredData();
  const total = data.length;
  const pages = Math.max(1, Math.ceil(total / PAGE_SIZE));
  if (currentPage > pages) currentPage = pages;
  const start = (currentPage - 1) * PAGE_SIZE;
  const slice = data.slice(start, start + PAGE_SIZE);

  const tbody = document.getElementById('histTableBody');
  const empty = document.getElementById('histEmpty');

  if (!slice.length) {
    tbody.innerHTML = '';
    empty.style.display = '';
  } else {
    empty.style.display = 'none';
    tbody.innerHTML = slice.map(r => {
      const pct = r.totalQuestions > 0 ? Math.round((r.correctAnswers / r.totalQuestions) * 100) : 0;
      const scoreClass = pct >= 75 ? 's-hist-score-pass' : pct >= 60 ? 's-hist-score-warn' : 's-hist-score-fail';
      const typeClass  = r.type === 'NGN' ? 's-hist-type-ngn' : 's-hist-type-trad';
      const date = r.timestamp ? new Date(r.timestamp).toLocaleString('en-US', {month:'short', day:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit'}) : '—';
      return `<tr>
        <td class="s-hist-exam-no">${r.exam_label}</td>
        <td><span class="s-hist-type-badge ${typeClass}">${r.type}</span></td>
        <td style="color:var(--s-muted); font-size:0.8rem;">${date}</td>
        <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${r.topic}">${r.topic}</td>
        <td><span class="${scoreClass}">${r.correctAnswers}/${r.totalQuestions} &nbsp;(${pct}%)</span></td>
        <td style="color:var(--s-muted);">${formatTime(r.totalTime)}</td>
        <td><a href="${r.link}" class="s-btn s-btn-primary" style="padding:5px 14px; font-size:0.75rem;"><i class="bi bi-eye me-1"></i>View</a></td>
      </tr>`;
    }).join('');
  }

  // Pagination info
  const from = total ? start + 1 : 0, to = Math.min(start + PAGE_SIZE, total);
  document.getElementById('histPageInfo').textContent = `Showing ${from}–${to} of ${total} record${total !== 1 ? 's' : ''}`;

  // Pagination buttons
  const btnWrap = document.getElementById('histPageBtns');
  let btns = `<button class="s-page-btn" onclick="goPage(${currentPage-1})" ${currentPage===1?'disabled':''}>&#8249;</button>`;
  for (let i = 1; i <= pages; i++) {
    if (pages <= 7 || i === 1 || i === pages || Math.abs(i - currentPage) <= 1) {
      btns += `<button class="s-page-btn ${i===currentPage?'active':''}" onclick="goPage(${i})">${i}</button>`;
    } else if (Math.abs(i - currentPage) === 2) {
      btns += `<button class="s-page-btn" disabled>…</button>`;
    }
  }
  btns += `<button class="s-page-btn" onclick="goPage(${currentPage+1})" ${currentPage===pages?'disabled':''}>&#8250;</button>`;
  btnWrap.innerHTML = btns;
}

function goPage(p) {
  const data  = getFilteredData();
  const pages = Math.max(1, Math.ceil(data.length / PAGE_SIZE));
  if (p < 1 || p > pages) return;
  currentPage = p;
  renderPage();
}

function setHistFilter(f) {
  currentFilter = f;
  // Tabs
  ['overall','trad','ngn'].forEach(t => document.getElementById('tab-'+t).classList.toggle('active', t === f));
  // KPI cards
  ['overall','trad','ngn'].forEach(t => document.getElementById('kpi-'+t).style.display = t === f ? '' : 'none');
  // Table heading
  const headings = { overall:'All Exam Records', trad:'Traditional Exam Records', ngn:'NGN Exam Records' };
  document.getElementById('table-heading').textContent = headings[f];
  renderTable();
}

// Initial render
renderPage();
</script>
</body>
</html>
