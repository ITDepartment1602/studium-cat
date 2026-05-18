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

$conceptIcons = [
    'Adult Health'               => 'bi bi-person-fill',
    'Child Health'               => 'bi bi-emoji-smile-fill',
    'Critical Care'              => 'bi bi-heart-pulse-fill',
    'Fundamentals'               => 'bi bi-gear-fill',
    'Leadership And Management'  => 'bi bi-people-fill',
    'Mental Health'              => 'bi bi-lightbulb-fill',
    'Pharmacology'               => 'bi bi-capsule',
    'Maternal And Newborn Health'=> 'bi bi-gender-female',
];

$pageTitle = 'Study Topics — Studium';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include '_layout/head.php'; ?>
  <style>
    /* Concept select card */
    .s-csel { cursor: pointer; display: block; }
    .s-csel-inner {
      border: 2px solid var(--s-border);
      border-radius: 14px;
      padding: 18px 14px;
      text-align: center;
      transition: border-color 0.2s, background 0.2s, box-shadow 0.2s;
      background: #fff;
    }
    .s-csel:hover .s-csel-inner {
      border-color: var(--s-accent);
      box-shadow: 0 4px 14px rgba(13,148,136,.12);
    }
    .s-csel.selected .s-csel-inner {
      border-color: var(--s-accent);
      background: rgba(13,148,136,.07);
      box-shadow: 0 4px 14px rgba(13,148,136,.15);
    }
    .s-csel-icon {
      width: 48px; height: 48px;
      border-radius: 12px;
      background: rgba(13,148,136,.1);
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem;
      color: var(--s-accent);
      margin: 0 auto 10px;
      transition: background 0.2s;
    }
    .s-csel.selected .s-csel-icon {
      background: var(--s-accent);
      color: #fff;
    }
    .s-csel-name {
      font-size: 0.8rem;
      font-weight: 700;
      color: var(--s-primary);
      line-height: 1.3;
    }

    /* Topic pill */
    .s-topic-pill {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 24px;
      background: rgba(13,148,136,.08);
      border: 1.5px solid rgba(13,148,136,.25);
      color: var(--s-primary);
      font-size: 0.78rem;
      font-weight: 600;
      line-height: 1;
    }
    .s-topic-pill i { color: var(--s-accent); font-size: 0.75rem; }
    .s-topic-pill .s-tp-count {
      font-size: 0.68rem;
      font-weight: 500;
      color: var(--s-muted);
    }
    .s-topic-pill.disabled {
      background: #f1f5f9;
      border-color: var(--s-border);
      color: var(--s-muted);
      opacity: 0.6;
    }
    .s-topic-pill.disabled i { color: var(--s-muted); }

    @keyframes spin { to { transform: rotate(360deg); } }

    .custom-confirm-button { background-color: #1e6091 !important; color: white !important; }
    .custom-cancel-button  { background-color: #ef4444 !important; color: white !important; }
  </style>
</head>
<body>

<?php include '_layout/sidebar.php'; ?>

<main class="s-main">
  <div class="s-page-header">
    <h1>NARC Traditional QBanks</h1>
    <p>Select a concept to begin your study session</p>
  </div>

  <!-- Step 1: Select Concept -->
  <div class="s-card mb-4">
    <div class="s-card-title"><i class="bi bi-grid-3x3-gap"></i> Step 1 — Select Concept</div>
    <?php if (empty($concepts)): ?>
      <p style="font-size:0.85rem; color:var(--s-muted);">No concepts found for this category.</p>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($concepts as $concept):
        $icon = $conceptIcons[$concept] ?? 'bi bi-journals';
      ?>
      <div class="col-xl-2 col-lg-3 col-md-4 col-sm-6 col-6">
        <label class="s-csel" data-concept="<?php echo htmlspecialchars($concept); ?>">
          <input type="radio" name="topics" value="<?php echo htmlspecialchars($concept); ?>"
                 class="conceptRadio" style="display:none;">
          <div class="s-csel-inner">
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
    <div class="d-flex align-items-center justify-content-between mb-3">
      <div class="s-card-title mb-0"><i class="bi bi-list-check"></i> Step 2 — Topics Included</div>
      <span id="topicsCount" style="font-size:0.72rem; color:var(--s-muted); font-weight:600;"></span>
    </div>
    <p style="font-size:0.78rem; color:var(--s-muted); margin-bottom:16px;" id="topicsDesc">
      <i class="bi bi-info-circle me-1"></i>All topics below are automatically included. Your test will draw 150 random questions from this concept.
    </p>
    <div id="topicsContainer" class="d-flex flex-wrap gap-2" style="min-height:72px; align-items:center;">
      <div id="topics-placeholder" style="display:flex; align-items:center; gap:10px; color:var(--s-muted); font-size:0.84rem;">
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
<div id="instrOverlay" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(10,22,40,0.97); align-items:center; justify-content:center; padding:20px;">
  <div style="background:#fff; border-radius:20px; max-width:560px; width:100%; padding:40px 36px; box-shadow:0 24px 80px rgba(0,0,0,.4); position:relative;">
    <div style="text-align:center; margin-bottom:28px;">
      <div style="width:64px; height:64px; border-radius:50%; background:rgba(13,148,136,.12); display:flex; align-items:center; justify-content:center; margin:0 auto 16px;">
        <i class="bi bi-shield-fill-check" style="font-size:1.8rem; color:#0d9488;"></i>
      </div>
      <h2 style="font-size:1.25rem; font-weight:800; color:#0a1628; margin:0 0 6px;">Study Session Instructions</h2>
      <p style="font-size:0.82rem; color:#64748b; margin:0;">Please read before starting your session.</p>
    </div>

    <div style="display:flex; flex-direction:column; gap:14px; margin-bottom:28px;">
      <div style="display:flex; gap:12px; align-items:flex-start; padding:14px 16px; background:#f8fafc; border-radius:10px; border-left:3px solid #0d9488;">
        <i class="bi bi-check2-circle" style="color:#0d9488; font-size:1.1rem; margin-top:1px; flex-shrink:0;"></i>
        <div>
          <div style="font-size:0.87rem; font-weight:700; color:#0f172a; margin-bottom:3px;">Complete All Questions</div>
          <div style="font-size:0.8rem; color:#64748b; line-height:1.5;">Results are displayed only after all 150 questions are answered.</div>
        </div>
      </div>
      <div style="display:flex; gap:12px; align-items:flex-start; padding:14px 16px; background:#f8fafc; border-radius:10px; border-left:3px solid #0d9488;">
        <i class="bi bi-laptop" style="color:#0d9488; font-size:1.1rem; margin-top:1px; flex-shrink:0;"></i>
        <div>
          <div style="font-size:0.87rem; font-weight:700; color:#0f172a; margin-bottom:3px;">Use One Device Only</div>
          <div style="font-size:0.8rem; color:#64748b; line-height:1.5;">Do not use more than one device simultaneously during the session.</div>
        </div>
      </div>
      <div style="display:flex; gap:12px; align-items:flex-start; padding:14px 16px; background:#f8fafc; border-radius:10px; border-left:3px solid #0d9488;">
        <i class="bi bi-arrow-counterclockwise" style="color:#0d9488; font-size:1.1rem; margin-top:1px; flex-shrink:0;"></i>
        <div>
          <div style="font-size:0.87rem; font-weight:700; color:#0f172a; margin-bottom:3px;">Avoid Reloading or Going Back</div>
          <div style="font-size:0.8rem; color:#64748b; line-height:1.5;">Do not reload the page or use the browser back button during the session.</div>
        </div>
      </div>
      <div style="display:flex; gap:12px; align-items:flex-start; padding:14px 16px; background:#f8fafc; border-radius:10px; border-left:3px solid #0d9488;">
        <i class="bi bi-camera" style="color:#0d9488; font-size:1.1rem; margin-top:1px; flex-shrink:0;"></i>
        <div>
          <div style="font-size:0.87rem; font-weight:700; color:#0f172a; margin-bottom:3px;">Report Missing Exhibits</div>
          <div style="font-size:0.8rem; color:#64748b; line-height:1.5;">Screenshot and report any questions that appear to be missing exhibits or images.</div>
        </div>
      </div>
    </div>

    <label style="display:flex; align-items:center; gap:10px; cursor:pointer; margin-bottom:20px; padding:12px 16px; border:1.5px solid #e2e8f0; border-radius:10px; user-select:none;">
      <input type="checkbox" id="instrAgree" style="width:18px; height:18px; accent-color:#0d9488; cursor:pointer;" onchange="document.getElementById('instrBeginBtn').disabled = !this.checked;">
      <span style="font-size:0.85rem; font-weight:600; color:#0f172a;">I understand and agree to follow these instructions.</span>
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
        const disabled = t.count === 0 ? ' disabled' : '';
        pillsHtml += `<div class="s-topic-pill${disabled}"><i class="bi bi-check-circle-fill"></i>${esc(t.system)}<span class="s-tp-count">(${t.count})</span></div>`;
        if (t.count > 0) {
          hiddenHtml += `<input type="hidden" class="topicHidden" value="${esc(encodeURIComponent(t.system)+'|'+encodeURIComponent(t.system))}" data-count="${t.count}">`;
          total += t.count;
        }
      });

      topicsContainer.innerHTML = pillsHtml;
      topicsHidden.innerHTML = hiddenHtml;
      topicsCount.textContent = `${topics.filter(t => t.count > 0).length} topics · ${total} questions`;

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
