<?php
include '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$user_id   = $_SESSION['user_id'];
$examTaken = intval($_GET['examTaken'] ?? 0);

$select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'");
$fetch  = mysqli_fetch_assoc($select);
date_default_timezone_set('Asia/Manila');
$daysLeft = floor((strtotime($fetch['dateexpired']) - time()) / 86400);
if ($daysLeft < 0) { header('Location: ../../logout.php'); exit; }

// Score summary
$correctRow = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as n FROM `review` WHERE studentId='$user_id' AND isCorrect=1 AND examTaken='$examTaken'"));
$wrongRow   = mysqli_fetch_assoc(mysqli_query($con, "SELECT COUNT(*) as n FROM `review` WHERE studentId='$user_id' AND isCorrect=0 AND examTaken='$examTaken'"));
$timeRow    = mysqli_fetch_assoc(mysqli_query($con, "SELECT totalTime FROM `review` WHERE studentId='$user_id' AND examTaken='$examTaken' AND questionNumber=150"));

$correctCount = (int)($correctRow['n'] ?? 0);
$wrongCount   = (int)($wrongRow['n']   ?? 0);
$totalTime    = (int)($timeRow['totalTime'] ?? 0);
$totalQ       = $correctCount + $wrongCount;
$scorePercent = $totalQ > 0 ? round(($correctCount / 150) * 100, 1) : 0;

function fmtTime($s) {
    $h = floor($s/3600); $m = floor(($s%3600)/60); $sec = $s%60;
    return ($h>0 ? $h.'h ' : '') . ($m>0 ? $m.'m ' : '') . $sec.'s';
}

// All questions for this exam
$reviewQuery = mysqli_query($con, "SELECT isCorrect, ans, questionNumber, questionId, topics1, system, cnc, timeTaken, correctAns FROM `review` WHERE studentId='$user_id' AND examTaken='$examTaken' ORDER BY questionNumber ASC");
$allQuestions = [];
while ($row = mysqli_fetch_assoc($reviewQuery)) {
    $allQuestions[] = $row;
}

$pageTitle = 'Exam Details — Studium';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_layout/head.php'; ?>
  <style>
    .hd-question-row {
      background: var(--s-card-bg);
      border: 1.5px solid var(--s-border);
      border-radius: 12px;
      padding: 14px 18px;
      display: flex;
      align-items: center;
      gap: 14px;
      transition: border-color 0.2s, box-shadow 0.2s;
    }
    .hd-question-row:hover { border-color: var(--s-accent); box-shadow: 0 4px 16px rgba(0,0,0,.06); }
    .hd-q-num {
      width: 36px; height: 36px; border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      font-weight: 700; font-size: 0.82rem; flex-shrink: 0;
    }
    .hd-q-num.correct { background: rgba(13,148,136,.12); color: var(--s-accent); }
    .hd-q-num.wrong   { background: rgba(239,68,68,.12);  color: #ef4444; }
    .hd-q-meta { flex: 1; min-width: 0; }
    .hd-q-meta .topic { font-size: 0.82rem; font-weight: 600; color: var(--s-primary); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .hd-q-meta .sub   { font-size: 0.74rem; color: var(--s-muted); }
    .hd-q-time { font-size: 0.78rem; color: var(--s-muted); flex-shrink: 0; }
    .hd-pagination { display: flex; align-items: center; gap: 6px; flex-wrap: wrap; }
    .hd-pg-btn {
      width: 34px; height: 34px; border-radius: 8px; border: 1.5px solid var(--s-border);
      background: var(--s-card-bg); color: var(--s-primary); font-size: 0.82rem; font-weight: 600;
      cursor: pointer; display: flex; align-items: center; justify-content: center; transition: all 0.15s;
    }
    .hd-pg-btn:hover { border-color: var(--s-accent); color: var(--s-accent); }
    .hd-pg-btn.active { background: var(--s-accent); border-color: var(--s-accent); color: #fff; }
    .hd-pg-btn:disabled { opacity: 0.35; cursor: default; }
  </style>
</head>
<body>

<?php include '_layout/sidebar.php'; ?>

<main class="s-main">

  <!-- Header nav -->
  <div class="d-flex align-items-center gap-2 mb-4">
    <a href="history.php" class="s-btn s-btn-outline" style="padding:7px 14px;">
      <i class="bi bi-arrow-left"></i> Back
    </a>
    <div class="ms-2">
      <h1 class="mb-0" style="font-size:1.3rem; font-weight:700; color:var(--s-primary);">Traditional Exam Details</h1>
      <p class="mb-0" style="font-size:0.78rem; color:var(--s-muted);">Exam #<?php echo $examTaken + 1; ?> &mdash; <?php echo $totalQ; ?> questions</p>
    </div>
  </div>

  <!-- Score Summary KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
      <div class="s-card text-center">
        <div style="font-size:1.5rem; color:var(--s-accent);"><i class="bi bi-check-circle-fill"></i></div>
        <h3 class="fw-bold mb-0" style="color:var(--s-accent);"><?php echo $correctCount; ?></h3>
        <p class="mb-0" style="font-size:0.75rem; color:var(--s-muted);">Correct</p>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="s-card text-center">
        <div style="font-size:1.5rem; color:#ef4444;"><i class="bi bi-x-circle-fill"></i></div>
        <h3 class="fw-bold mb-0" style="color:#ef4444;"><?php echo $wrongCount; ?></h3>
        <p class="mb-0" style="font-size:0.75rem; color:var(--s-muted);">Incorrect</p>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="s-card text-center">
        <div style="font-size:1.5rem; color:var(--s-primary);"><i class="bi bi-percent"></i></div>
        <h3 class="fw-bold mb-0" style="color:var(--s-primary);"><?php echo $scorePercent; ?>%</h3>
        <p class="mb-0" style="font-size:0.75rem; color:var(--s-muted);">Score</p>
      </div>
    </div>
    <div class="col-md-3 col-6">
      <div class="s-card text-center">
        <div style="font-size:1.5rem; color:var(--s-muted);"><i class="bi bi-stopwatch"></i></div>
        <h5 class="fw-bold mb-0" style="color:var(--s-primary); font-size:0.95rem;"><?php echo fmtTime($totalTime); ?></h5>
        <p class="mb-0" style="font-size:0.75rem; color:var(--s-muted);">Total Time</p>
      </div>
    </div>
  </div>

  <!-- Score Bar -->
  <div class="s-card mb-4">
    <div class="s-card-title mb-2"><i class="bi bi-bar-chart-fill me-1"></i> Score: <?php echo $correctCount; ?> / 150</div>
    <div class="s-progress" style="height:12px; border-radius:8px; background:var(--s-border);">
      <div class="s-progress-bar" id="scoreBar" style="width:0%; border-radius:8px; transition:width 1s ease;"></div>
    </div>
    <div class="d-flex justify-content-between mt-1" style="font-size:0.72rem; color:var(--s-muted);">
      <span>0%</span><span>50%</span><span>100%</span>
    </div>
  </div>

  <!-- Search + Questions List -->
  <div class="s-card">
    <div class="d-flex align-items-center justify-content-between mb-3 gap-2 flex-wrap">
      <div class="s-card-title mb-0"><i class="bi bi-list-columns-reverse me-1"></i> Question Breakdown</div>
      <input type="text" id="hdSearch" placeholder="Search topic, system..." class="form-control" style="max-width:240px; font-size:0.82rem; border-radius:8px; border:1.5px solid var(--s-border);" oninput="hdFilter()">
    </div>

    <div id="hdList"></div>

    <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
      <div id="hdInfo" style="font-size:0.78rem; color:var(--s-muted);"></div>
      <div class="hd-pagination" id="hdPager"></div>
    </div>
  </div>

</main>

<script>
const PAGE_SIZE = 10;
const allRows = <?php echo json_encode(array_map(function($row) {
    $tm = (int)$row['timeTaken'];
    return [
        'num'     => $row['questionNumber'],
        'id'      => str_pad($row['questionId'], 5, '0', STR_PAD_LEFT),
        'topic'   => $row['topics1'],
        'system'  => $row['system'],
        'cnc'     => $row['cnc'],
        'time'    => ($tm >= 60 ? floor($tm/60).'m ' : '') . ($tm%60).'s',
        'correct' => (bool)$row['isCorrect'],
        'ans'     => $row['ans'],
        'corrAns' => $row['correctAns'],
        'qId'     => $row['questionId'],
        'isCorrect' => $row['isCorrect'],
        'timeTaken' => $row['timeTaken'],
        'topics1'   => $row['topics1'],
        'sysRaw'    => $row['system'],
        'cncRaw'    => $row['cnc'],
    ];
}, $allQuestions)); ?>;

let filtered = [...allRows];
let curPage = 1;

function hdFilter() {
    const q = document.getElementById('hdSearch').value.toLowerCase();
    filtered = allRows.filter(r =>
        (r.topic||'').toLowerCase().includes(q) ||
        (r.system||'').toLowerCase().includes(q) ||
        (r.cnc||'').toLowerCase().includes(q)
    );
    curPage = 1;
    render();
}

function goPage(p) { curPage = p; render(); }

function render() {
    const total = filtered.length;
    const totalPages = Math.max(1, Math.ceil(total / PAGE_SIZE));
    if (curPage > totalPages) curPage = totalPages;

    const start = (curPage - 1) * PAGE_SIZE;
    const slice = filtered.slice(start, start + PAGE_SIZE);

    const list = document.getElementById('hdList');
    if (!slice.length) {
        list.innerHTML = '<p style="color:var(--s-muted); text-align:center; padding:24px 0;">No questions match your search.</p>';
    } else {
        list.innerHTML = slice.map(r => {
            const cls = r.correct ? 'correct' : 'wrong';
            const icon = r.correct ? '<i class="bi bi-check-lg"></i>' : '<i class="bi bi-x-lg"></i>';
            const badge = r.correct
                ? '<span class="s-badge s-badge-teal" style="font-size:0.7rem;"><i class="bi bi-check-lg me-1"></i>Correct</span>'
                : '<span class="s-badge s-badge-red" style="font-size:0.7rem;"><i class="bi bi-x-lg me-1"></i>Wrong</span>';
            const viewUrl = `rationale/qpages.php?isCorrect=${r.isCorrect}&questionId=${r.qId}&topics1=${encodeURIComponent(r.topics1)}&system=${encodeURIComponent(r.sysRaw)}&cnc=${encodeURIComponent(r.cncRaw)}&timeTaken=${r.timeTaken}&ans=${encodeURIComponent(r.ans)}&correctAns=${encodeURIComponent(r.corrAns)}&questionNumber=${r.num}`;
            return `<div class="hd-question-row mb-2">
              <div class="hd-q-num ${cls}">${icon}</div>
              <div class="hd-q-meta">
                <div class="topic">#${r.num} — ${r.topic || 'Unknown Topic'}</div>
                <div class="sub">${r.system || ''} ${r.system && r.cnc ? '·' : ''} ${r.cnc || ''}</div>
              </div>
              <div class="d-flex align-items-center gap-2 flex-shrink-0">
                ${badge}
                <span class="hd-q-time"><i class="bi bi-clock me-1"></i>${r.time}</span>
                <a href="${viewUrl}" target="_blank" class="s-btn s-btn-outline" style="padding:4px 10px; font-size:0.72rem; white-space:nowrap;">
                  <i class="bi bi-eye"></i> View
                </a>
              </div>
            </div>`;
        }).join('');
    }

    document.getElementById('hdInfo').textContent = `Showing ${start + 1}–${Math.min(start + PAGE_SIZE, total)} of ${total} questions`;

    // Pager
    const pager = document.getElementById('hdPager');
    let html = `<button class="hd-pg-btn" onclick="goPage(${curPage-1})" ${curPage===1?'disabled':''}>‹</button>`;
    for (let p = 1; p <= totalPages; p++) {
        if (totalPages > 7 && p > 2 && p < totalPages - 1 && Math.abs(p - curPage) > 1) {
            if (p === 3 || p === totalPages - 2) html += `<span style="padding:0 4px; color:var(--s-muted);">…</span>`;
            continue;
        }
        html += `<button class="hd-pg-btn ${p===curPage?'active':''}" onclick="goPage(${p})">${p}</button>`;
    }
    html += `<button class="hd-pg-btn" onclick="goPage(${curPage+1})" ${curPage===totalPages?'disabled':''}>›</button>`;
    pager.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('scoreBar').style.width = '<?php echo $scorePercent; ?>%';
    render();
});
</script>
</body>
</html>
