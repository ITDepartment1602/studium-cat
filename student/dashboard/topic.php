<?php
include '../../config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}
$user_id = $_SESSION['user_id'];
$kilanlan = isset($_GET['kilanlan']) ? $_GET['kilanlan'] : '';

$select = mysqli_query($con, "SELECT * FROM `login` WHERE id = '$user_id'") or die('query failed');
$fetch  = mysqli_fetch_assoc($select);

date_default_timezone_set('Asia/Manila');
$daysLeft = floor((strtotime($fetch['dateexpired']) - time()) / 86400);
if ($daysLeft < 0) { header('Location: ../../logout.php'); exit; }

// Fetch concepts for this category
$conceptQuery = mysqli_query($con, "SELECT * FROM topics1 WHERE kilanlan = '$kilanlan'");
$concepts = [];
while ($row = mysqli_fetch_array($conceptQuery)) {
    $concepts[] = $row['title'];
}

// Pre-fetch ALL topics for ALL concepts at PHP time — eliminates AJAX latency on concept click
$allConceptTopics = [];
foreach ($concepts as $concept) {
    $conceptEsc = mysqli_real_escape_string($con, $concept);
    $tq = mysqli_query($con, "SELECT system, COUNT(*) as cnt FROM question WHERE topics1 = '$conceptEsc' AND system IS NOT NULL AND system != '' GROUP BY system ORDER BY system ASC");
    $rows = [];
    while ($tr = mysqli_fetch_assoc($tq)) {
        $rows[] = ['system' => $tr['system'], 'count' => (int)$tr['cnt']];
    }
    $allConceptTopics[$concept] = $rows;
}

// Font Awesome icons — chosen to visually match each concept
$conceptIcons = [
    'adult health'                => 'fa-solid fa-user-large',
    'child health'                => 'fa-solid fa-children',
    'critical care'               => 'fa-solid fa-heart-pulse',
    'fundamentals'                => 'fa-solid fa-book-medical',
    'leadership and management'   => 'fa-solid fa-user-tie',
    'mental health'               => 'fa-solid fa-brain',
    'pharmacology'                => 'fa-solid fa-pills',
    'maternal and newborn health' => 'fa-solid fa-baby-carriage',
];

// Accent color per concept — gives the selector grid distinct, organized visuals
$conceptColors = [
    'adult health'                => '#0ea5e9',
    'child health'                => '#f59e0b',
    'critical care'               => '#ef4444',
    'fundamentals'                => '#0d9488',
    'leadership and management'   => '#6366f1',
    'mental health'               => '#a855f7',
    'pharmacology'                => '#22c55e',
    'maternal and newborn health' => '#ec4899',
];
$conceptFallbackColor = '#0d9488';

$pageTitle = 'Study Topics — Studium';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_layout/head.php'; ?>
  <style>
    /* ── Step section headers ── */
    .s-step-head {
      display: flex; align-items: center; gap: 12px;
      margin-bottom: 18px;
    }
    .s-step-num {
      width: 30px; height: 30px; flex-shrink: 0;
      border-radius: 9px;
      background: linear-gradient(135deg, var(--s-primary, #007CBF), var(--s-accent, #0d9488));
      color: #fff; font-weight: 800; font-size: 0.9rem;
      display: flex; align-items: center; justify-content: center;
      box-shadow: 0 3px 8px rgba(13,148,136,.25);
    }
    .s-step-titles { line-height: 1.25; }
    .s-step-kicker {
      font-size: 0.66rem; font-weight: 700; letter-spacing: 0.8px;
      text-transform: uppercase; color: var(--s-accent, #0d9488);
    }
    .s-step-name { font-size: 0.98rem; font-weight: 800; color: var(--s-primary); }

    /* ── Concept select card ── */
    .s-csel { cursor: pointer; display: block; --csc: #0d9488; }
    .s-csel-inner {
      position: relative;
      border: 2px solid var(--s-border);
      border-radius: 16px;
      padding: 20px 14px 18px;
      text-align: center;
      transition: transform 0.18s ease, border-color 0.18s, background 0.18s, box-shadow 0.18s;
      background: #fff;
      overflow: hidden;
    }
    .s-csel:hover .s-csel-inner {
      border-color: color-mix(in srgb, var(--csc) 55%, #fff);
      box-shadow: 0 8px 20px color-mix(in srgb, var(--csc) 18%, transparent);
      transform: translateY(-3px);
    }
    .s-csel.selected .s-csel-inner {
      border-color: var(--csc);
      background: color-mix(in srgb, var(--csc) 7%, #fff);
      box-shadow: 0 8px 22px color-mix(in srgb, var(--csc) 22%, transparent);
    }
    .s-csel-icon {
      width: 54px; height: 54px;
      border-radius: 14px;
      background: color-mix(in srgb, var(--csc) 12%, #fff);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.45rem;
      color: var(--csc);
      margin: 0 auto 12px;
      transition: background 0.18s, color 0.18s, transform 0.18s;
    }
    .s-csel:hover .s-csel-icon { transform: scale(1.06); }
    .s-csel.selected .s-csel-icon {
      background: var(--csc);
      color: #fff;
      box-shadow: 0 4px 12px color-mix(in srgb, var(--csc) 40%, transparent);
    }
    .s-csel-name {
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--s-primary);
      line-height: 1.3;
    }
    .s-csel.selected .s-csel-name { color: var(--csc); }
    /* Selected check badge */
    .s-csel-check {
      position: absolute; top: 9px; right: 9px;
      width: 20px; height: 20px; border-radius: 50%;
      background: var(--csc); color: #fff;
      display: flex; align-items: center; justify-content: center;
      font-size: 0.6rem;
      opacity: 0; transform: scale(0.5);
      transition: opacity 0.18s, transform 0.18s;
    }
    .s-csel.selected .s-csel-check { opacity: 1; transform: scale(1); }

    /* ── Topics grid ── */
    .s-topic-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(212px, 1fr));
      gap: 10px;
    }
    .s-topic-chip {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 11px 13px;
      border-radius: 12px;
      background: #fff;
      border: 1.5px solid var(--s-border);
      transition: border-color 0.18s, box-shadow 0.18s, transform 0.18s;
    }
    .s-topic-chip:hover {
      border-color: color-mix(in srgb, var(--s-accent, #0d9488) 50%, #fff);
      box-shadow: 0 5px 14px rgba(13,148,136,.12);
      transform: translateY(-2px);
    }
    .s-tc-check {
      flex-shrink: 0;
      width: 26px; height: 26px;
      border-radius: 8px;
      background: rgba(13,148,136,.1);
      color: var(--s-accent, #0d9488);
      display: flex; align-items: center; justify-content: center;
      font-size: 0.82rem;
    }
    .s-tc-name {
      flex: 1; min-width: 0;
      font-size: 0.8rem; font-weight: 600;
      color: var(--s-primary); line-height: 1.25;
    }
    .s-tc-count {
      flex-shrink: 0;
      font-size: 0.68rem; font-weight: 700;
      color: var(--s-accent, #0d9488);
      background: rgba(13,148,136,.1);
      border-radius: 7px; padding: 3px 8px;
    }
    .s-topic-chip.disabled { background: #f8fafc; opacity: 0.6; }
    .s-topic-chip.disabled .s-tc-check,
    .s-topic-chip.disabled .s-tc-count {
      background: #eef2f6; color: var(--s-muted);
    }
    .s-topic-chip.disabled .s-tc-name { color: var(--s-muted); }
    #topics-placeholder { grid-column: 1 / -1; }

    /* Topics count badge */
    .s-topics-count {
      display: inline-flex; align-items: center; gap: 6px;
      font-size: 0.72rem; font-weight: 700;
      color: var(--s-accent, #0d9488);
      background: rgba(13,148,136,.1);
      border-radius: 20px; padding: 5px 12px;
    }
    .s-topics-count:empty { display: none; }

    @keyframes spin { to { transform: rotate(360deg); } }

    .custom-confirm-button { background-color: #1e6091 !important; color: white !important; }
    .custom-cancel-button  { background-color: #ef4444 !important; color: white !important; }

    /* ── Mobile responsive ── */
    @media (max-width: 768px) {
      body { overflow-x: hidden; }
      .s-page-header h1 { font-size: 1.25rem; }
      .s-page-header p  { font-size: 0.82rem; }
      .s-card { padding: 16px; }
      .s-card-title { font-size: 0.95rem; }
      .s-csel-name { font-size: 0.75rem; }
      .s-csel-icon i { font-size: 1.1rem; }
      .s-topic-grid { grid-template-columns: repeat(auto-fill, minmax(170px, 1fr)); gap: 8px; }
      .s-topic-chip { padding: 9px 11px; }
      .s-tc-name { font-size: 0.76rem; }
      #startTestButton { width: 100%; padding: 12px 20px !important; font-size: 0.95rem !important; }
      #startRow { justify-content: stretch !important; }

      .instr-modal-box { padding: 24px 20px !important; border-radius: 16px !important; }
      .instr-modal-box h2 { font-size: 1.1rem !important; }
      .instr-modal-box p { font-size: 0.78rem !important; }
    }
    @media (max-width: 480px) {
      .s-page-header h1 { font-size: 1.1rem; }
      .s-card { padding: 13px; }
      .s-csel-inner { padding: 10px 6px; }
      .s-csel-name { font-size: 0.7rem; line-height: 1.2; }
      .s-csel-icon { width: 32px; height: 32px; }
      .s-csel-icon i { font-size: 1rem; }
      .s-topic-grid { grid-template-columns: 1fr 1fr; gap: 7px; }
      .s-topic-chip { padding: 8px 9px; gap: 7px; }
      .s-tc-check { width: 22px; height: 22px; font-size: 0.72rem; }
      .s-tc-name { font-size: 0.72rem; }
      .s-tc-count { font-size: 0.62rem; padding: 2px 6px; }

      .instr-modal-box { padding: 18px 14px !important; border-radius: 14px !important; }
      .instr-modal-box h2 { font-size: 1rem !important; }
      .instr-modal-box [style*="flex:1"], .instr-modal-box [style*="flex:2"] {
        font-size: 0.82rem !important; padding: 11px !important;
      }
    }
  </style>
</head>
<body>

<?php include '_layout/sidebar.php'; ?>

<main class="s-main">
  <div class="s-page-header">
    <h1>Traditional QBanks</h1>
    <p>Select a concept to begin your study session</p>
  </div>

  <!-- Step 1: Select Concept -->
  <div class="s-card mb-4">
    <div class="s-step-head">
      <span class="s-step-num">1</span>
      <span class="s-step-titles">
        <span class="s-step-kicker">Step 1</span><br>
        <span class="s-step-name">Select a Concept</span>
      </span>
    </div>
    <?php if (empty($concepts)): ?>
      <p style="font-size:0.85rem; color:var(--s-muted);">No concepts found for this category.</p>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($concepts as $concept):
        $key   = strtolower($concept);
        $icon  = $conceptIcons[$key]  ?? 'fa-solid fa-book-medical';
        $color = $conceptColors[$key] ?? $conceptFallbackColor;
      ?>
      <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6">
        <label class="s-csel" data-concept="<?php echo htmlspecialchars($concept); ?>" style="--csc:<?php echo $color; ?>;">
          <input type="radio" name="topics" value="<?php echo htmlspecialchars($concept); ?>"
                 class="conceptRadio" style="display:none;">
          <div class="s-csel-inner">
            <span class="s-csel-check"><i class="fa-solid fa-check"></i></span>
            <div class="s-csel-icon"><i class="<?php echo $icon; ?>"></i></div>
            <div class="s-csel-name"><?php echo htmlspecialchars($concept); ?></div>
          </div>
        </label>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Step 2: Topics (always visible) -->
  <div class="s-card mb-4" id="topicsCard">
    <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
      <div class="s-step-head mb-0">
        <span class="s-step-num">2</span>
        <span class="s-step-titles">
          <span class="s-step-kicker">Step 2</span><br>
          <span class="s-step-name">Topics Included</span>
        </span>
      </div>
      <span id="topicsCount" class="s-topics-count"></span>
    </div>
    <p style="font-size:0.78rem; color:var(--s-muted); margin-bottom:16px;" id="topicsDesc">
      <i class="bi bi-info-circle me-1"></i>All topics below are automatically included. Your test will draw 150 random questions from this concept.
    </p>
    <div id="topicsContainer" class="s-topic-grid" style="min-height:72px;">
      <div id="topics-placeholder" style="display:flex; align-items:center; justify-content:center; gap:10px; color:var(--s-muted); font-size:0.84rem; min-height:72px;">
        <i class="bi bi-arrow-up-circle" style="font-size:1.3rem; color:var(--s-border-2);"></i>
        <span>Select a concept above to see the topics included</span>
      </div>
    </div>
    <div id="topicsHidden"></div>
  </div>

  <!-- Start Button (always visible, disabled until concept chosen) -->
  <div class="d-flex justify-content-end mb-4" id="startRow">
    <button class="s-btn s-btn-teal" id="startTestButton" disabled style="padding:12px 36px; font-size:1rem; opacity:0.5; cursor:not-allowed;">
      <i class="bi bi-play-circle-fill me-2"></i> Start Study Session
    </button>
  </div>
</main>


<!-- ── Study Session Instruction Overlay ── -->
<div id="instrOverlay" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(10,22,40,0.97); align-items:center; justify-content:center; padding:16px; overflow-y:auto;">
  <div class="instr-modal-box" style="background:#fff; border-radius:20px; max-width:560px; width:100%; padding:28px 24px; box-shadow:0 24px 80px rgba(0,0,0,.4); position:relative; max-height:92vh; overflow-y:auto;">
    <div style="text-align:center; margin-bottom:18px;">
      <div style="width:48px; height:48px; border-radius:50%; background:rgba(13,148,136,.12); display:flex; align-items:center; justify-content:center; margin:0 auto 12px;">
        <i class="bi bi-shield-fill-check" style="font-size:1.4rem; color:#0d9488;"></i>
      </div>
      <h2 style="font-size:1.05rem; font-weight:800; color:#0a1628; margin:0 0 4px;">Study Session Instructions</h2>
      <p style="font-size:0.76rem; color:#64748b; margin:0;">Please read before starting your session.</p>
    </div>

    <div style="display:flex; flex-direction:column; gap:8px; margin-bottom:16px;">
      <div style="display:flex; gap:10px; align-items:flex-start; padding:10px 12px; background:#f8fafc; border-radius:8px; border-left:3px solid #0d9488;">
        <i class="bi bi-check2-circle" style="color:#0d9488; font-size:1rem; margin-top:1px; flex-shrink:0;"></i>
        <div>
          <div style="font-size:0.78rem; font-weight:700; color:#0f172a; margin-bottom:2px;">Complete All Questions</div>
          <div style="font-size:0.72rem; color:#64748b; line-height:1.45;">Results are displayed only after all 150 questions are answered.</div>
        </div>
      </div>
      <div style="display:flex; gap:10px; align-items:flex-start; padding:10px 12px; background:#f8fafc; border-radius:8px; border-left:3px solid #0d9488;">
        <i class="bi bi-laptop" style="color:#0d9488; font-size:1rem; margin-top:1px; flex-shrink:0;"></i>
        <div>
          <div style="font-size:0.78rem; font-weight:700; color:#0f172a; margin-bottom:2px;">Use One Device Only</div>
          <div style="font-size:0.72rem; color:#64748b; line-height:1.45;">Do not use more than one device simultaneously during the session.</div>
        </div>
      </div>
      <div style="display:flex; gap:10px; align-items:flex-start; padding:10px 12px; background:#f8fafc; border-radius:8px; border-left:3px solid #0d9488;">
        <i class="bi bi-arrow-counterclockwise" style="color:#0d9488; font-size:1rem; margin-top:1px; flex-shrink:0;"></i>
        <div>
          <div style="font-size:0.78rem; font-weight:700; color:#0f172a; margin-bottom:2px;">Avoid Reloading or Going Back</div>
          <div style="font-size:0.72rem; color:#64748b; line-height:1.45;">Do not reload the page or use the browser back button during the session.</div>
        </div>
      </div>
      <div style="display:flex; gap:10px; align-items:flex-start; padding:10px 12px; background:#f8fafc; border-radius:8px; border-left:3px solid #0d9488;">
        <i class="bi bi-eye" style="color:#0d9488; font-size:1rem; margin-top:1px; flex-shrink:0;"></i>
        <div>
          <div style="font-size:0.78rem; font-weight:700; color:#0f172a; margin-bottom:2px;">Report Missing Exhibits</div>
          <div style="font-size:0.72rem; color:#64748b; line-height:1.45;">If a question appears to be missing an image or exhibit, report it using the Feedback button inside the session.</div>
        </div>
      </div>
    </div>

    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin-bottom:14px; padding:10px 14px; border:1.5px solid #e2e8f0; border-radius:10px; user-select:none;">
      <input type="checkbox" id="instrAgree" style="width:17px; height:17px; accent-color:#0d9488; cursor:pointer;" onchange="document.getElementById('instrBeginBtn').disabled = !this.checked;">
      <span style="font-size:0.78rem; font-weight:600; color:#0f172a;">I understand and agree to follow these instructions.</span>
    </label>

    <input type="hidden" id="instrDestUrl" value="">

    <div style="display:flex; gap:10px;">
      <button onclick="document.getElementById('instrOverlay').style.display='none';"
        style="flex:1; padding:12px; border:1.5px solid #e2e8f0; background:#fff; border-radius:10px; font-size:0.9rem; font-weight:600; color:#64748b; cursor:pointer; font-family:inherit;">
        Cancel
      </button>
      <button id="instrBeginBtn" disabled
        onclick="window.location.href = document.getElementById('instrDestUrl').value;"
        style="flex:2; padding:12px; background:#0d9488; border:none; border-radius:10px; font-size:0.9rem; font-weight:700; color:#fff; cursor:pointer; font-family:inherit; transition:opacity 0.2s;"
        onmouseover="if(!this.disabled)this.style.opacity='0.88'" onmouseout="this.style.opacity='1'">
        <i class="bi bi-play-circle-fill" style="margin-right:6px;"></i> Begin Session
      </button>
    </div>
  </div>
</div>

<script>
// All topic data pre-computed server-side — zero AJAX latency on concept click
const allTopics = <?= json_encode($allConceptTopics, JSON_UNESCAPED_UNICODE) ?>;

document.addEventListener('DOMContentLoaded', function () {
  const conceptLabels  = document.querySelectorAll('.s-csel');
  const topicsContainer = document.getElementById('topicsContainer');
  const topicsHidden   = document.getElementById('topicsHidden');
  const topicsCount    = document.getElementById('topicsCount');

  let selectedConcept = null;

  conceptLabels.forEach(label => {
    label.addEventListener('click', function () {
      conceptLabels.forEach(l => l.classList.remove('selected'));
      this.classList.add('selected');
      this.querySelector('.conceptRadio').checked = true;
      selectedConcept = this.dataset.concept;

      const topics = allTopics[selectedConcept] || [];
      if (topics.length === 0) {
        topicsContainer.innerHTML = '<p style="color:var(--s-muted); font-size:0.85rem;">No topics found for this concept.</p>';
        topicsHidden.innerHTML = '';
        topicsCount.textContent = '';
        return;
      }

      let pillsHtml = '', hiddenHtml = '', total = 0;
      topics.forEach(t => {
        const disabled = t.count === 0;
        const icon = disabled ? 'fa-solid fa-circle-minus' : 'fa-solid fa-circle-check';
        pillsHtml += `<div class="s-topic-chip${disabled ? ' disabled' : ''}">`
          + `<span class="s-tc-check"><i class="${icon}"></i></span>`
          + `<span class="s-tc-name">${esc(t.system)}</span>`
          + `<span class="s-tc-count">${t.count}</span>`
          + `</div>`;
        if (t.count > 0) {
          hiddenHtml += `<input type="hidden" class="topicHidden" value="${esc(encodeURIComponent(t.system)+'|'+encodeURIComponent(t.system))}" data-count="${t.count}">`;
          total += t.count;
        }
      });

      topicsContainer.innerHTML = pillsHtml;
      topicsHidden.innerHTML = hiddenHtml;
      topicsCount.innerHTML = `<i class="fa-solid fa-layer-group"></i> ${topics.filter(t => t.count > 0).length} topics · ${total} questions`;

      const btn = document.getElementById('startTestButton');
      if (total >= 150) {
        btn.disabled = false; btn.style.opacity = ''; btn.style.cursor = '';
      } else {
        btn.disabled = true; btn.style.opacity = '0.5'; btn.style.cursor = 'not-allowed';
      }
    });
  });

  function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

  document.getElementById('startTestButton').addEventListener('click', function (e) {
    e.preventDefault();

    if (!selectedConcept) {
      Swal.fire({ icon: 'error', title: 'Oops...', text: 'Please select a concept!' });
      return;
    }

    const hiddenInputs = document.querySelectorAll('#topicsHidden .topicHidden');
    if (hiddenInputs.length === 0) {
      Swal.fire({ icon: 'warning', title: 'No Topics', text: 'No topics are available for this concept yet.' });
      return;
    }

    const sumOfCounts = Array.from(hiddenInputs).reduce((sum, h) => sum + parseInt(h.getAttribute('data-count') || 0), 0);
    if (sumOfCounts < 150) {
      Swal.fire({
        title: 'Not Enough Questions',
        text: `This concept has ${sumOfCounts} questions. At least 150 are needed to start a session.`,
        icon: 'warning'
      });
      return;
    }

    // Build the destination URL
    const selectedTopics = Array.from(hiddenInputs).map(h => h.value);
    const destUrl = `question/pre-loader.php?topics1=${encodeURIComponent(selectedConcept)}&topics2=${selectedTopics.join(',')}&kilanlan=<?= htmlspecialchars($kilanlan) ?>&id=<?= $fetch['id'] ?>`;

    // Show custom instruction overlay
    document.getElementById('instrDestUrl').value = destUrl;
    document.getElementById('instrOverlay').style.display = 'flex';
    document.getElementById('instrAgree').checked = false;
    document.getElementById('instrBeginBtn').disabled = true;
  });
});
</script>
</body>
</html>
